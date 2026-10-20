<section class="card p-3 mb-4" aria-labelledby="pending-memberships-title">
    <h2 id="pending-memberships-title" class="h4">Đăng ký trực tuyến chờ thanh toán</h2>
    <p class="text-muted">Chỉ xác nhận sau khi đã nhận đủ phí. Hệ thống cấp hoặc gia hạn thẻ và ghi nhận một giao dịch phí thẻ. Mức phí hiển thị là mức đã báo cho độc giả khi gửi đăng ký.</p>
    <div class="table-responsive"><table class="table"><thead><tr><th>Đăng ký</th><th>Độc giả</th><th>Thời hạn / Phí</th><th>Xử lý tại quầy</th></tr></thead><tbody>
    @forelse($pendingRequests as $registration)
        <tr><td>#{{ $registration->id }}<div class="small text-muted">{{ $registration->created_at->format('d/m/Y H:i') }}</div></td><td>{{ $registration->user?->name }}<div class="small text-muted">{{ $registration->user?->user_code }} · {{ $registration->user?->phone }}</div></td><td>{{ $registration->months }} tháng<br><strong>{{ number_format($registration->fee_amount,0,',','.') }} đ</strong></td><td>
            <form method="POST" action="{{ route('cards.requests.review',$registration) }}" class="membership-request-actions mb-2">@csrf<input type="hidden" name="decision" value="approve">
                <label for="request-method-{{ $registration->id }}" class="form-label">Phương thức đã nhận tiền</label><select id="request-method-{{ $registration->id }}" name="method" class="form-select form-select-sm mb-2" required><option value="cash">Tiền mặt</option><option value="transfer">Chuyển khoản tại quầy</option></select>
                <div class="form-check mb-2"><input id="request-paid-{{ $registration->id }}" type="checkbox" name="payment_received" value="1" class="form-check-input" required><label for="request-paid-{{ $registration->id }}" class="form-check-label">Đã nhận đủ {{ number_format($registration->fee_amount,0,',','.') }} đ</label></div>
                <button type="submit" class="btn btn-primary btn-sm">Xác nhận thanh toán & cấp thẻ</button>
            </form>
            <form method="POST" action="{{ route('cards.requests.review',$registration) }}">@csrf<input type="hidden" name="decision" value="reject"><button type="submit" class="btn btn-outline-secondary btn-sm">Từ chối đăng ký</button></form>
        </td></tr>
    @empty<tr><td colspan="4" class="text-center py-3">Không có đăng ký chờ thanh toán.</td></tr>@endforelse
    </tbody></table></div>{{ $pendingRequests->links() }}
</section>
