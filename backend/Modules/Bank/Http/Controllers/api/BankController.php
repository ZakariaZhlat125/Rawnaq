<?php

namespace Modules\Bank\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Modules\Bank\Models\Bank;
use Modules\Bank\Http\Requests\BankRequest;


class BankController extends Controller
{
    public function store(BankRequest $request)
    {
        $userId = auth()->user()->id;
        $data = $request->all();
        $data['user_id'] = $userId;

        if ($request->filled('bank_id')) {
            // Edit existing bank
            $bank = Bank::where('id', $request->bank_id)->where('user_id', $userId)->first();

            if (!$bank) {
                return response()->json(['message' => __('Bank not found')], 404);
            }

            $bank->update($data);

            if ($request->hasFile('bank_attachment')) {
                $bank->clearMediaCollection('bank_attachments');
                $bank->addMedia($request->file('bank_attachment'))->toMediaCollection('bank_attachments');
            }

            return response()->json([
                'message' => __('messages.bank_updated'),
                'data' => $bank,
            ], 200);
        } else {
            // Create new bank
            $bank = Bank::create($data);

            if ($request->hasFile('bank_attachment')) {
                $bank->addMedia($request->file('bank_attachment'))->toMediaCollection('bank_attachments');
            }

            return response()->json([
                'message' => __('messages.bank_added'),
                'data' => $bank,
            ], 201);
        }
    }

    public function showBank(Request $request)
    {
        
        $perPage = $request->get('per_page', 25); // Default per_page to 25
        $page = $request->get('page', 1); // Default page to 1
        $userId = $request->get('user_id');

        
        $banks = Bank::where('user_id', $userId)
            ->paginate($perPage, ['*'], 'page', $page);

        // Return the paginated response
        return response()->json([
            'message' => __('User bank details retrieved successfully'),
            'data' => $banks->items(), // List of bank records
            'pagination' => [
                'total' => $banks->total(),
                'current_page' => $banks->currentPage(),
                'per_page' => $banks->perPage(),
                'last_page' => $banks->lastPage(),
            ],
        ], 200);
    }

    public function setDefault(Request $request)
    {
        
        $bankId = $request->get('id'); 
        $userId = auth()->user()->id; 

        
        $bank = Bank::where('id', $bankId)->where('user_id', $userId)->first();

        if (!$bank) {
            return response()->json(['message' => __('Bank not found or does not belong to the user')], 404);
        }

        
        Bank::where('user_id', $userId)->update(['is_default' => 0]);

        
        $bank->update(['is_default' => 1]);

        return response()->json([
            'message' => __('Default bank updated successfully'),
            'data' => $bank,
        ], 200);
    }

    

    public function deleteBank($bankId)
    {
        $userId = auth()->user()->id;

        // Find the bank by its ID and ensure it belongs to the current user
        $bank = Bank::where('id', $bankId)->where('user_id', $userId)->first();

        // If the bank doesn't exist, return a 404 error
        if (!$bank) {
            return response()->json(['message' => __('Bank not found or does not belong to the user')], 404);
        }

        if ($bank->media()->exists()) {
            $bank->media()->delete();
        }
    
        // Permanently delete the bank record
        $bank->forceDelete();
    
        return response()->json([
            'message' => __('messages.bank_deleted'),
        ], 200);
    }
}
