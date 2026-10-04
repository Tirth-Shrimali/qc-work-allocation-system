@if(is_numeric($i))
    <tr>
        <td>
            <select name="tests[{{ $i }}][test_type_id]" class="form-select form-select-sm @error("tests.$i.test_type_id") is-invalid @enderror" data-test-type required>
                <option value="">Select test…</option>
                @foreach($testTypes as $t)
                    <option value="{{ $t->id }}" @selected(($row['test_type_id'] ?? '') == $t->id)>{{ $t->test_code }} — {{ $t->name }}</option>
                @endforeach
            </select>
            @error("tests.$i.test_type_id")<div class="invalid-feedback">{{ $message }}</div>@enderror
        </td>
        <td>
            <select name="tests[{{ $i }}][test_method_id]" class="form-select form-select-sm">
                <option value="">Select method…</option>
                @php($currentType = $row['test_type_id'] ?? null)
                @foreach($testTypes->where('id', $currentType)->flatMap->methods as $m)
                    <option value="{{ $m->id }}" @selected(($row['test_method_id'] ?? '') == $m->id)>{{ $m->method_code }} — {{ $m->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" name="tests[{{ $i }}][specification]" class="form-control form-control-sm"
                   value="{{ $row['specification'] ?? '' }}" placeholder="e.g. 98.0–102.0 %">
        </td>
        <td>
            <select name="tests[{{ $i }}][priority]" class="form-select form-select-sm">
                @foreach(['Low', 'Normal', 'High', 'Critical'] as $p)
                    <option value="{{ $p }}" @selected(($row['priority'] ?? 'Normal') === $p)>{{ $p }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" step="0.25" min="0.25" max="999" name="tests[{{ $i }}][estimated_duration]"
                   class="form-control form-control-sm" value="{{ $row['estimated_duration'] ?? '2' }}">
        </td>
        <td class="text-end">
            <button type="button" class="btn btn-outline-danger btn-sm" data-remove-row title="Remove row">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
@endif

@once
    @push('scripts')
        <script type="text/template" id="testRowTemplate">
            <tr>
                <td>
                    <select name="tests[__INDEX__][test_type_id]" class="form-select form-select-sm" data-test-type required>
                        <option value="">Select test…</option>
                        @foreach($testTypes as $t)
                            <option value="{{ $t->id }}">{{ $t->test_code }} — {{ $t->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select name="tests[__INDEX__][test_method_id]" class="form-select form-select-sm">
                        <option value="">Select method…</option>
                    </select>
                </td>
                <td><input type="text" name="tests[__INDEX__][specification]" class="form-control form-control-sm" placeholder="e.g. 98.0–102.0 %"></td>
                <td>
                    <select name="tests[__INDEX__][priority]" class="form-select form-select-sm">
                        @foreach(['Low', 'Normal', 'High', 'Critical'] as $p)
                            <option value="{{ $p }}" @selected(($row['priority'] ?? 'Normal') === $p)>{{ $p }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" step="0.25" min="0.25" max="999" name="tests[__INDEX__][estimated_duration]" class="form-control form-control-sm" value="2"></td>
                <td class="text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm" data-remove-row title="Remove row"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        </script>
    @endpush
@endonce
