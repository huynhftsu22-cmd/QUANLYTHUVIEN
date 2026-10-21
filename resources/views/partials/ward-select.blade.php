{{-- Ô chọn địa chỉ: danh sách phường của TP.HCM (config/hcm_wards.php). Truyền vào $selected = giá trị đang chọn. --}}
<select
    id="address"
    class="form-select @error('address') is-invalid @enderror"
    name="address"
    autocomplete="address-level3"
>
    <option value="">-- Chọn phường --</option>
    @foreach(config('hcm_wards') as $ward)
        <option value="{{ $ward }}" @selected(($selected ?? null) === $ward)>{{ $ward }}</option>
    @endforeach
</select>
@error('address')
    <div class="invalid-feedback">{{ $message }}</div>
@enderror
