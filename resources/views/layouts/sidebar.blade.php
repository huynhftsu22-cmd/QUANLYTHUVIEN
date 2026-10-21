@php
    $isAdmin = auth()->user()->role !== 'user';
    $groups = $isAdmin ? [
        ['label' => 'Không gian làm việc', 'items' => [
            ['Tổng quan', 'statistics.index', ['statistics.*'], 'overview'],
            ['Tra cứu sách', 'books.index', ['books.index', 'books.show'], 'search'],
        ]],
        ['label' => 'Kho sách', 'items' => [
            ['Quản lý sách', 'books.admin.index', ['books.admin.*', 'books.create', 'books.edit', 'books.store', 'books.update', 'copies.*'], 'book'],
            ['Tác giả', 'authors.index', ['authors.*'], 'pen'],
            ['Thể loại', 'categories.index', ['categories.*'], 'grid'],
        ]],
        ['label' => 'Độc giả', 'items' => [
            ['Người dùng', 'users.index', ['users.*'], 'users'],
            ['Thẻ thành viên', 'cards.index', ['cards.*'], 'card'],
            ['Xác minh học sinh/sinh viên', 'verifications.index', ['verifications.*'], 'shield'],
        ]],
        ['label' => 'Mượn trả & thanh toán', 'items' => [
            ['Đặt mượn – bàn giao', 'borrow.index', ['borrow.*'], 'out'],
            ['Trả sách', 'returns.index', ['returns.*'], 'in'],
            ['Thanh toán & tiền cọc', 'payments.index', ['payments.*'], 'wallet'],
        ]],
    ] : [
        ['label' => 'Thư viện', 'items' => [
            ['Khám phá sách', 'books.index', ['books.index', 'books.show'], 'book'],
            ['Lịch sử mượn', 'history.index', ['history.*', 'borrow.*'], 'history'],
        ]],
        ['label' => 'Tài khoản của bạn', 'items' => [
            ['Thẻ thành viên', 'cards.mine', ['cards.*'], 'card'],
            ['Xác minh học sinh/sinh viên', 'verifications.mine', ['verifications.*'], 'shield'],
        ]],
    ];
@endphp
<aside class="sidebar ss-sidebar" aria-label="Thanh điều hướng">
    <div class="ss-sidebar-heading">
        <span class="ss-sidebar-emblem">@include('layouts.sidebar-icon', ['icon' => 'book'])</span>
        <div><strong>Say Sách</strong><span>{{ $isAdmin ? 'Không gian quản trị' : 'Góc đọc của bạn' }}</span></div>
        <button class="ss-menu-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-expanded="false" aria-controls="sidebar-menu">
            @include('layouts.sidebar-icon', ['icon' => 'menu'])<span>Menu</span>
        </button>
    </div>
    <div class="collapse ss-sidebar-body" id="sidebar-menu">
        <nav class="ss-sidebar-nav" aria-label="{{ $isAdmin ? 'Chức năng quản trị' : 'Chức năng độc giả' }}">
            @foreach($groups as $group)
                <section class="ss-nav-group" aria-labelledby="sidebar-group-{{ $loop->index }}">
                    <h2 id="sidebar-group-{{ $loop->index }}" class="ss-nav-label">{{ $group['label'] }}</h2>
                    @foreach($group['items'] as [$label, $route, $patterns, $icon])
                        @php($isActive = request()->routeIs(...$patterns))
                        <a href="{{ route($route) }}" class="ss-nav-link{{ $isActive ? ' is-active' : '' }}" @if($isActive) aria-current="page" @endif>
                            <span class="ss-nav-icon">@include('layouts.sidebar-icon', ['icon' => $icon])</span>
                            <span>{{ $label }}</span><span class="ss-active-dot" aria-hidden="true"></span>
                        </a>
                    @endforeach
                </section>
            @endforeach
        </nav>
        <div class="ss-sidebar-account">
            <span class="ss-account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            <div><strong>{{ auth()->user()->name }}</strong><span>{{ $isAdmin ? 'Quản trị viên' : 'Độc giả' }}</span></div>
        </div>
    </div>
</aside>
