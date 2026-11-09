<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\PaymentGatewayField;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** /app/payment-gateways: gateways customers can pay with and their credentials. */
class PaymentGatewaysController extends Controller
{
    private const MAX_VALUE_LENGTH = 20000;

    public function __construct(private PaymentGatewayManager $manager)
    {
        $this->middleware('permission:manage_payment_gateways');
    }

    public function index()
    {
        $gateways = PaymentGateway::with('fields')->ordered()->get();

        return view('backend.payment_gateways.index', [
            'gateways' => $gateways,
            'drivers' => $this->driverLabels(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[a-z0-9_-]+$/', 'not_in:cod', Rule::unique('payment_gateways', 'code')],
            'driver' => ['required', Rule::in(array_keys($this->manager->drivers()))],
            'name_ar' => 'required|string|max:150',
            'name_en' => 'nullable|string|max:150',
            'sort_order' => 'nullable|integer|min:0|max:100000',
        ]);

        // Starts switched off: it has no credentials yet.
        $gateway = DB::transaction(function () use ($data) {
            $gateway = PaymentGateway::create(array_merge($data, ['is_active' => false, 'sort_order' => $data['sort_order'] ?? 0]));
            $this->manager->syncFields($gateway);

            return $gateway;
        });

        return redirect()->route('backend.payment-gateways.edit', $gateway)
            ->with('success', __('payment_gateways.created'));
    }

    public function edit(PaymentGateway $gateway)
    {
        // Picks up fields a driver started declaring after this gateway was created.
        $this->manager->syncFields($gateway);

        return view('backend.payment_gateways.edit', [
            'gateway' => $gateway->load('fields'),
            'driverLabel' => $this->driverLabels()[$gateway->driver] ?? $gateway->driver,
            'driverKeys' => array_keys($this->manager->fieldTemplate($gateway->driver)),
        ]);
    }

    public function update(Request $request, PaymentGateway $gateway)
    {
        $gateway->load('fields');

        $data = $request->validate([
            'name_ar' => 'required|string|max:150',
            'name_en' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0|max:100000',
            'is_active' => 'nullable|boolean',
            'fields' => 'nullable|array',
            'fields.*' => 'nullable|string|max:' . self::MAX_VALUE_LENGTH,
            'clear' => 'nullable|array',
        ]);

        $input = $data['fields'] ?? [];
        $clear = array_filter($data['clear'] ?? []);
        $values = [];
        $errors = [];

        foreach ($gateway->fields as $field) {
            // A field the form did not send (e.g. added meanwhile in another tab) keeps its value.
            if (! array_key_exists($field->id, $input) && empty($clear[$field->id])) {
                $values[$field->id] = $field->value;
                continue;
            }

            $value = $this->normalize($input[$field->id] ?? null);

            if ($field->is_secret) {
                // An empty box keeps the saved secret, since secrets are never sent back to the page.
                $value = empty($clear[$field->id]) ? ($value ?? $field->value) : null;
            }

            if ($value !== null && $field->type === PaymentGatewayField::TYPE_SELECT && ! array_key_exists($value, $field->options ?? [])) {
                $errors["fields.{$field->id}"] = __('payment_gateways.invalid_option', ['field' => $field->label]);
            }

            $values[$field->id] = $value;
        }

        $isActive = (bool) ($data['is_active'] ?? false);
        if ($isActive) {
            foreach ($gateway->fields as $field) {
                if ($field->is_required && $values[$field->id] === null) {
                    $errors["fields.{$field->id}"] = __('payment_gateways.required_to_activate', ['field' => $field->label]);
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($gateway, $data, $values, $isActive) {
            $gateway->update([
                'name_ar' => $data['name_ar'],
                'name_en' => $data['name_en'] ?? null,
                'description' => $data['description'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $isActive,
            ]);

            foreach ($gateway->fields as $field) {
                if ($field->value !== $values[$field->id]) {
                    $field->update(['value' => $values[$field->id]]);
                }
            }
        });

        return back()->with('success', __('payment_gateways.saved'));
    }

    public function destroy(PaymentGateway $gateway)
    {
        if ($gateway->hasPendingAttempts()) {
            return back()->with('error', __('payment_gateways.has_pending_attempts'));
        }

        $gateway->delete();

        return redirect()->route('backend.payment-gateways.index')->with('success', __('payment_gateways.deleted'));
    }

    public function storeField(Request $request, PaymentGateway $gateway)
    {
        $data = $request->validate([
            'key' => [
                'required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('payment_gateway_fields', 'key')->where('payment_gateway_id', $gateway->id),
            ],
            'label' => 'required|string|max:150',
            'type' => ['required', Rule::in([PaymentGatewayField::TYPE_TEXTAREA, PaymentGatewayField::TYPE_SELECT])],
            'options' => 'nullable|required_if:type,select|string|max:2000',
            'help_text' => 'nullable|string|max:255',
            'is_required' => 'nullable|boolean',
            'is_secret' => 'nullable|boolean',
        ]);

        $options = $data['type'] === PaymentGatewayField::TYPE_SELECT ? $this->parseOptions($data['options'] ?? '') : null;
        if ($data['type'] === PaymentGatewayField::TYPE_SELECT && ! $options) {
            throw ValidationException::withMessages(['options' => __('payment_gateways.options_format')]);
        }

        $isRequired = (bool) ($data['is_required'] ?? false);

        $gateway->fields()->create([
            'key' => $data['key'],
            'label' => $data['label'],
            'type' => $data['type'],
            'options' => $options,
            'help_text' => $data['help_text'] ?? null,
            'is_required' => $isRequired,
            'is_secret' => (bool) ($data['is_secret'] ?? false),
            'sort_order' => (int) $gateway->fields()->max('sort_order') + 10,
        ]);

        // A new required field is empty, so the gateway cannot stay live without it.
        if ($isRequired && $gateway->is_active) {
            $gateway->update(['is_active' => false]);

            return back()->with('success', __('payment_gateways.field_added_deactivated'));
        }

        return back()->with('success', __('payment_gateways.field_added'));
    }

    public function destroyField(PaymentGateway $gateway, PaymentGatewayField $field)
    {
        // The driver reads these keys; removing one would break the integration.
        if (array_key_exists($field->key, $this->manager->fieldTemplate($gateway->driver))) {
            return back()->with('error', __('payment_gateways.field_is_builtin'));
        }

        $field->delete();

        return back()->with('success', __('payment_gateways.field_deleted'));
    }

    private function driverLabels(): array
    {
        return collect($this->manager->drivers())->map(fn (string $class) => $class::label())->all();
    }

    private function normalize(?string $value): ?string
    {
        $value = $value === null ? '' : trim($value);

        return $value === '' ? null : $value;
    }

    /** "value=Label" per line; a line without "=" uses the value as its label. */
    private function parseOptions(string $text): array
    {
        $options = [];
        foreach (preg_split('/\R/', $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            [$value, $label] = array_pad(array_map('trim', explode('=', $line, 2)), 2, null);
            if ($value !== '') {
                $options[$value] = $label ?: $value;
            }
        }

        return $options;
    }
}
