@extends('layouts.app')
@section('title', 'Chi tiết sách')
@section('content')
<h1 class="page-title mb-3">Chi tiết sách</h1>
<div class="card p-4 book-detail">
    <div class="row g-4">
        <div class="col-md-3">@include('books.cover', ['coverClass' => 'detail-cover'])</div>
        <div class="col-md-9">
            <div class="text-muted mb-2">Mã sách: {{ $book->book_code }}</div>
            <h2 class="book-detail-title">{{ $book->title }}</h2>
            <p>Tác giả: {{ $book->author?->name }} | Thể loại: {{ $book->category?->name }}</p>
            <p>{{ $book->description }}</p>
            @if($book->catalog_entry)
                <p>Số trang: {{ $book->catalog_entry['pages'] }} · Ngôn ngữ: Tiếng Việt</p>
            @endif
            <p>Nhà xuất bản: {{ $book->publisher ?: 'Chưa xác minh ấn bản' }} | Năm xuất bản: {{ $book->publication_year ?: 'Chưa xác minh ấn bản' }}</p>
            <p>Giá thuê cơ bản/ngày: {{ number_format($policy->daily_rate, 0, ',', '.') }} đ</p>
            <p>Giá bìa: {{ number_format($book->cover_price, 0, ',', '.') }} đ</p>
            <p>Bản sao có sẵn: {{ $book->copies->where('status','available')->count() }}</p>
            @if(auth()->check() && auth()->user()->role === 'user')
                <form method="POST" action="{{ route('borrow.reserve') }}" id="rental-form" data-quote-url="{{ route('books.quote', $book) }}">
                    @csrf
                    <input type="hidden" name="book_id" value="{{ $book->id }}">
                    <label for="rental-days" class="form-label">Số ngày thuê (1–{{ $maxDays }})</label>
                    <input id="rental-days" class="form-control" type="number" name="rental_days" value="{{ $days }}" min="1" max="{{ $maxDays }}" step="1" required>
                    <section class="rental-breakdown" aria-label="Chi phí mượn sách" aria-live="polite" id="rental-quote">
                        <dl>
                            <div><dt>Tiền thuê cho <span id="quote-days">{{ $days }}</span> ngày (sau ưu đãi)</dt><dd id="quote-fee">{{ number_format($estimate['rental_fee'], 0, ',', '.') }} đ</dd></div>
                            <div><dt>Đã giảm</dt><dd id="quote-discount">{{ number_format($estimate['discount_amount'], 0, ',', '.') }} đ</dd></div>
                            <div><dt>Tiền cần cọc</dt><dd id="quote-deposit">{{ number_format($estimate['deposit_amount'], 0, ',', '.') }} đ</dd></div>
                            <div class="quote-total"><dt>Tổng thanh toán khi nhận sách</dt><dd id="quote-total">{{ number_format($estimate['rental_fee'] + $estimate['deposit_amount'], 0, ',', '.') }} đ</dd></div>
                        </dl>
                        <div class="small text-muted mt-2">Hạn trả dự kiến: <span id="quote-due">{{ \Carbon\Carbon::parse($estimate['due_date'])->format('d/m/Y') }}</span></div>
                    </section>
                    <p id="quote-error" role="alert" hidden></p>
                    <p class="deposit-explanation">Tiền cọc được hoàn lại khi trả sách, sau khi khấu trừ tiền phạt trả trễ hoặc bồi thường hư hỏng/mất sách nếu có. Trả đúng hạn, sách nguyên vẹn được hoàn đủ tiền cọc. Tiền thuê không nằm trong khoản hoàn cọc.</p>
                    <p class="small text-muted">Chi phí dự kiến nếu nhận sách hôm nay. Khi bàn giao tại quầy, nhân viên đối chiếu ưu đãi, thời hạn thẻ và xác nhận số tiền trước khi thu.</p>
                    <noscript><button class="btn btn-outline-secondary" type="submit" formmethod="GET" formaction="{{ route('books.show', $book) }}">Tính lại chi phí</button></noscript>
                    <div class="book-actions">
                        <button class="btn btn-primary" id="reserve-button" @disabled(!$book->is_active || !$book->copies->contains('status','available')) data-unavailable="{{ !$book->is_active || !$book->copies->contains('status','available') ? '1' : '0' }}">Đặt giữ sách</button>
                        <a class="btn btn-outline-primary" href="{{ route('books.index', request()->only(['q','category_id','author_id','page'])) }}">← Quay lại</a>
                    </div>
                </form>
                <script src="{{ asset('js/rental-quote.js') }}" defer></script>
            @else
                <div class="book-actions">
                    @guest
                        <a class="btn btn-outline-primary" href="{{ route('login') }}">Đăng nhập để mượn</a>
                    @endguest
                    <a class="btn btn-outline-primary" href="{{ route('books.index', request()->only(['q','category_id','author_id','page'])) }}">← Quay lại</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
