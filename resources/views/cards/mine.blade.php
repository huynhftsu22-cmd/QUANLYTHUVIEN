@extends('layouts.app')
@section('title', 'Thẻ thành viên của tôi')
@section('content')
<h1 class="mb-3">Thẻ thành viên của tôi</h1>
<div class="card p-4 membership-summary mb-3">
    <p>Độc giả: <strong>{{ auth()->user()->name }}</strong> · Mã độc giả: {{ auth()->user()->user_code }}</p>
    <p>Phí thẻ: <strong>{{ number_format($policy->card_fee_monthly, 0, ',', '.') }} đ/tháng</strong>. Đăng ký trực tuyến bên dưới và thanh toán tại quầy để kích hoạt thẻ.</p>
    <p class="mb-0">Thẻ đang hiệu lực và còn hạn đến hết ngày trả dự kiến được miễn phí thuê sách; thành viên vẫn đặt cọc {{ $policy->deposit_rate }}% giá bìa như độc giả thường{{ (int)$policy->deposit_rate === 100 ? '.' : ', làm tròn lên nghìn đồng.' }} Không thu cọc riêng cho thẻ thành viên; cọc tính theo từng sách đang mượn. Tiền cọc được hoàn lại khi trả sách, sau khi khấu trừ phạt/bồi thường nếu có. Thẻ tạm khóa, hết hạn hoặc không phủ hết thời gian mượn không được áp dụng ưu đãi thẻ cho phiếu đó.</p>
</div>
<section class="card p-4 mb-4 membership-register" aria-labelledby="membership-register-title">
    <h2 id="membership-register-title" class="h4 mb-3">{{ $currentCard ? 'Đăng ký gia hạn thẻ' : 'Đăng ký thẻ thành viên' }}</h2>
    @if($pending)
        <div class="mb-3"><span class="badge text-bg-warning">Chờ thanh toán tại quầy</span></div>
        <p>Đăng ký <strong>#{{ $pending->id }}</strong> · {{ $pending->months }} tháng · <strong>{{ number_format($pending->fee_amount, 0, ',', '.') }} đ</strong>.</p>
        <p>Đăng ký đã được gửi đến thư viện. Thẻ chỉ được kích hoạt hoặc gia hạn sau khi nhân viên xác nhận đã nhận đủ phí.</p>
        <form method="POST" action="{{ route('cards.requests.cancel', $pending) }}">@csrf<button class="btn btn-outline-secondary" type="submit">Hủy đăng ký</button></form>
    @elseif($currentCard?->status === 'suspended')
        <p class="mb-0">Thẻ đang tạm khóa. Vui lòng liên hệ thư viện để mở khóa trước khi gia hạn.</p>
    @else
        <form method="POST" action="{{ route('cards.requests.store') }}" id="membership-registration" data-monthly-fee="{{ (int)$policy->card_fee_monthly }}">
            @csrf
            <div class="row g-3 align-items-end mb-3">
                <div class="col-sm-5"><label for="membership-months" class="form-label">Thời hạn đăng ký</label><select id="membership-months" name="months" class="form-select" required>@for($month=1;$month<=12;$month++)<option value="{{ $month }}" @selected((int)old('months',1)===$month)>{{ $month }} tháng</option>@endfor</select></div>
                <div class="col-sm-7"><span class="d-block text-muted">Tổng phí thẻ</span><output id="membership-total" class="membership-price" aria-live="polite">{{ number_format((int)$policy->card_fee_monthly * max(1,min(12,(int)old('months',1))), 0, ',', '.') }} đ</output></div>
            </div>
            @if($currentCard)
                <p>Thẻ hiện tại <strong>{{ $currentCard->card_code }}</strong> còn hạn đến {{ \Carbon\Carbon::parse($currentCard->expires_at)->format('d/m/Y') }}. Khi xác nhận thanh toán, thời gian gia hạn được cộng tiếp vào hạn thẻ còn hiệu lực; nếu thẻ đã hết hạn thì cấp từ ngày xác nhận.</p>
            @else
                <p>Thời hạn thẻ bắt đầu từ ngày nhân viên xác nhận thanh toán. Phí thẻ được thu riêng với tiền thuê sách và tiền cọc.</p>
            @endif
            <p class="text-muted">Thanh toán bằng tiền mặt hoặc chuyển khoản tại quầy. Gửi đăng ký chưa phát sinh giao dịch thanh toán và chưa áp dụng ưu đãi thẻ.</p>
            <button class="btn btn-primary" type="submit">{{ $currentCard ? 'Gửi đăng ký gia hạn' : 'Gửi đăng ký thẻ' }}</button>
        </form>
        <script src="{{ asset('js/membership-registration.js').'?v=20261021-1' }}" defer></script>
    @endif
</section>
<h2 class="h4 mb-3">Thẻ đã cấp</h2>
<div class="card table-responsive">
    <table class="table"><thead><tr><th>Mã thẻ</th><th>Ngày cấp</th><th>Hạn sử dụng</th><th>Trạng thái</th><th>Phí cấp/gia hạn gần nhất</th></tr></thead>
        <tbody>
        @forelse($cards as $card)
            @php($state = $card->status === 'active' && \Carbon\Carbon::parse($card->expires_at)->lt(today()) ? 'expired' : $card->status)
            <tr><td>{{ $card->card_code }}</td><td>{{ \Carbon\Carbon::parse($card->issued_at)->format('d/m/Y') }}</td><td>{{ \Carbon\Carbon::parse($card->expires_at)->format('d/m/Y') }}</td><td>{{ config('saysach_labels.card_status.'.$state, $state) }}</td><td>{{ number_format($card->fee_amount, 0, ',', '.') }} đ</td></tr>
        @empty
            <tr><td colspan="5" class="text-center py-4">Bạn chưa có thẻ thành viên. Đăng ký ở biểu mẫu phía trên để bắt đầu.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $cards->links() }}</div>
<h2 class="h4 mt-4 mb-3">Lịch sử đăng ký thẻ</h2>
<div class="card table-responsive"><table class="table"><thead><tr><th>Đăng ký</th><th>Ngày gửi</th><th>Thời hạn</th><th>Phí thẻ</th><th>Trạng thái</th><th>Mã thẻ</th></tr></thead><tbody>
@forelse($requests as $registration)
    <tr><td>#{{ $registration->id }}</td><td>{{ $registration->created_at->format('d/m/Y H:i') }}</td><td>{{ $registration->months }} tháng</td><td>{{ number_format($registration->fee_amount, 0, ',', '.') }} đ</td><td>{{ ['pending'=>'Chờ thanh toán tại quầy','approved'=>'Đã cấp/gia hạn','rejected'=>'Đã từ chối','cancelled'=>'Đã hủy'][$registration->status] ?? $registration->status }}</td><td>{{ $registration->card?->card_code ?? '—' }}</td></tr>
@empty<tr><td colspan="6" class="text-center py-4">Chưa có đăng ký thẻ.</td></tr>@endforelse
</tbody></table></div><div class="mt-3">{{ $requests->links() }}</div>
@endsection
