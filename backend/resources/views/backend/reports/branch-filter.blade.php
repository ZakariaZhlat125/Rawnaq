<select id="column_branch_id" name="branch_id" class="select2 form-control"
    aria-label="{{ __('branch.select_branch') }}">
    <option value="">{{ __('branch.all_branches') }}</option>
    @foreach ($branches as $branch)
        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
    @endforeach
</select>
