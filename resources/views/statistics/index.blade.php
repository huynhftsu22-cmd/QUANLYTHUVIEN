@extends('layouts.app')

@section('title', 'Thống kê thư viện')

@section('content')
<style>
    .ss-statistics {
        --stats-green: #214e3b;
        --stats-text: #405347;
        --stats-border: #e5ebe4;
        --stats-header: #eef3eb;
    }

    .ss-statistics .stats-page-title {
        margin-bottom: 24px;
    }

    /* Các ô tổng số */
    .ss-statistics .stats-summary-card {
        height: 100%;
        padding: 22px 24px;
        border-radius: 12px;
    }

    .ss-statistics .stats-summary-label {
        font-family: inherit;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.5;
        color: var(--stats-text);
        margin-bottom: 16px;
    }

    .ss-statistics .stats-summary-value {
        font-family: inherit;
        font-size: 30px;
        font-weight: 700;
        line-height: 1.2;
        color: var(--stats-green);
        margin: 0;
        font-variant-numeric: tabular-nums;
    }

    /* Tiêu đề các bảng */
    .ss-statistics .stats-section-title {
        font-family: var(--ss-heading-font, 'Noto Serif', serif);
        font-size: 18px;
        font-weight: 700;
        line-height: 1.5;
        color: var(--stats-green);
        margin: 0 0 18px;
    }

    .ss-statistics .stats-recent-card {
        padding: 22px;
        border-radius: 12px;
        margin-bottom: 20px;
    }

    /* Ba bảng có cùng chiều rộng và chiều cao */
    .ss-statistics .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: stretch;
        gap: 18px;
    }

    .ss-statistics .stats-panel {
        display: flex;
        flex-direction: column;
        min-width: 0;
        height: 100%;
        padding: 20px;
        border-radius: 12px;
    }

    .ss-statistics .stats-grid .stats-section-title {
        min-height: 54px;
        margin-bottom: 14px;
    }

    /* Nội dung bảng */
    .ss-statistics .stats-table {
        width: 100%;
        margin-bottom: 0;
        font-family: inherit;
        font-size: 14px;
        line-height: 1.6;
        color: var(--stats-text);
    }

    .ss-statistics .stats-table > thead > tr > th {
        padding: 12px;
        background: var(--stats-header);
        color: var(--stats-green);
        font-size: 13px;
        font-weight: 700;
        letter-spacing: normal;
        text-transform: none;
        vertical-align: middle;
        border-bottom: 1px solid var(--stats-border);
    }

    .ss-statistics .stats-table > tbody > tr > td {
        padding: 14px 12px;
        color: var(--stats-text);
        vertical-align: middle;
        border-bottom: 1px solid var(--stats-border);
    }

    .ss-statistics .stats-table > tbody > tr:last-child > td {
        border-bottom: 0;
    }

    .ss-statistics .stats-compact-table {
        table-layout: fixed;
    }

    .ss-statistics .stats-compact-table th:first-child {
        width: 66%;
    }

    .ss-statistics .stats-compact-table th:last-child {
        width: 34%;
    }

    .ss-statistics .stats-compact-table th:first-child,
    .ss-statistics .stats-compact-table td:first-child {
        overflow-wrap: anywhere;
    }

    .ss-statistics .stats-compact-table th:last-child,
    .ss-statistics .stats-compact-table td:last-child {
        text-align: right;
    }

    .ss-statistics .stats-number {
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }

    .ss-statistics .stats-table td.stats-empty {
        padding: 28px 12px;
        text-align: center;
        color: #6c757d;
        font-weight: 400;
    }

    /* Phân trang */
    .ss-statistics .stats-pagination {
        margin-top: auto;
        padding-top: 20px;
    }

    .ss-statistics .stats-pagination nav {
        max-width: 100%;
    }

    .ss-statistics .stats-pagination .pagination {
        flex-wrap: wrap;
        gap: 4px;
        margin-bottom: 0;
    }

    .ss-statistics .stats-pagination .page-link {
        font-family: inherit;
        font-size: 13px;
    }

    .ss-statistics .stats-pagination p {
        font-size: 13px;
        line-height: 1.6;
    }

    .ss-statistics .stats-load-error {
        margin-top: 14px;
        color: #a12d28;
        font-size: 13px;
        line-height: 1.6;
    }

    #ss-book-statistics {
        overflow-anchor: none;
    }

    #ss-book-statistics[aria-busy="true"] .stats-pagination {
        opacity: 0.5;
        pointer-events: none;
    }

    @media (max-width: 991.98px) {
        .ss-statistics .stats-grid {
            grid-template-columns: 1fr;
        }

        .ss-statistics .stats-grid .stats-section-title {
            min-height: 0;
        }
    }

    @media (max-width: 575.98px) {
        .ss-statistics .stats-summary-card {
            padding: 20px;
        }

        .ss-statistics .stats-summary-value {
            font-size: 28px;
        }

        .ss-statistics .stats-panel,
        .ss-statistics .stats-recent-card {
            padding: 16px;
        }
    }
</style>

<div class="ss-statistics">
    <h1 class="stats-page-title">Thống kê thư viện</h1>

    <form class="row g-2 mb-4" method="GET">
        <div class="col-auto">
            <label for="stats-from" class="form-label">
                Từ ngày
            </label>

            <input
                id="stats-from"
                type="date"
                name="from"
                value="{{ $from }}"
                class="form-control"
            >
        </div>

        <div class="col-auto">
            <label for="stats-to" class="form-label">
                Đến ngày
            </label>

            <input
                id="stats-to"
                type="date"
                name="to"
                value="{{ $to }}"
                class="form-control"
            >
        </div>

        <div class="col-auto align-self-end">
            <button class="btn btn-primary" type="submit">
                Lọc
            </button>
        </div>
    </form>

    <div class="row g-3">
        @foreach($stats as $label => $value)
            <div class="col-md-4">
                <div class="card stats-summary-card">
                    <div class="stats-summary-label">
                        {{ $label }}
                    </div>

                    <h3 class="stats-summary-value">
                        {{ number_format($value, 0, ',', '.') }}
                    </h3>
                </div>
            </div>
        @endforeach
    </div>

    <a
        class="btn btn-outline-primary my-3"
        href="{{ route('statistics.export', [
            'from' => $from,
            'to' => $to,
        ]) }}"
    >
        Xuất báo cáo bảng tính
    </a>

    <section class="card stats-recent-card">
        <h2 class="stats-section-title">Phiếu gần đây</h2>

        <div class="table-responsive">
            <table class="table stats-table">
                <thead>
                    <tr>
                        <th scope="col">Mã phiếu</th>
                        <th scope="col">Độc giả</th>
                        <th scope="col">Sách</th>
                        <th scope="col">Trạng thái</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($recent as $record)
                        <tr>
                            <td>#{{ $record->id }}</td>

                            <td>{{ $record->user?->name }}</td>

                            <td>
                                {{ $record->bookCopy?->book?->title }}
                            </td>

                            <td>
                                {{ config(
                                    'saysach_labels.borrow_status.'.$record->status,
                                    'Chưa xác định'
                                ) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="stats-empty">
                                Không có phiếu trong kỳ.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="stats-grid">
        <section class="card stats-panel">
            <h2 class="stats-section-title">
                Bản sao theo trạng thái
            </h2>

            <table class="table stats-table stats-compact-table">
                <thead>
                    <tr>
                        <th scope="col">Trạng thái</th>
                        <th scope="col">Số bản</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($copyStatuses as $status => $count)
                        <tr>
                            <td>
                                {{ config(
                                    'saysach_labels.copy_status.'.$status,
                                    'Chưa xác định'
                                ) }}
                            </td>

                            <td class="stats-number">
                                {{ number_format($count, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="stats-empty">
                                Chưa có bản sao.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="card stats-panel">
            <h2 class="stats-section-title">
                Doanh thu theo ngày
            </h2>

            <table class="table stats-table stats-compact-table">
                <thead>
                    <tr>
                        <th scope="col">Ngày thu</th>
                        <th scope="col">Số tiền</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($dailyRevenue as $day)
                        <tr>
                            <td>
                                {{ \Carbon\Carbon::parse($day->day)->format('d/m/Y') }}
                            </td>

                            <td class="stats-number">
                                {{ number_format($day->total, 0, ',', '.') }} đ
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="stats-empty">
                                Chưa có doanh thu trong kỳ.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section
            id="ss-book-statistics"
            class="card stats-panel"
            aria-labelledby="ss-book-statistics-title"
            aria-busy="false"
        >
            <h2
                id="ss-book-statistics-title"
                class="stats-section-title"
            >
                Thống kê lượt mượn sách
            </h2>

            <table class="table stats-table stats-compact-table">
                <thead>
                    <tr>
                        <th scope="col">Đầu sách</th>
                        <th scope="col">Lượt mượn</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($topBooks as $book)
                        <tr>
                            <td>{{ $book->title }}</td>

                            <td class="stats-number">
                                {{ number_format($book->borrows_count, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="stats-empty">
                                Chưa có đầu sách.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($topBooks->hasPages())
                <div class="stats-pagination">
                    {{ $topBooks
                        ->appends(['from' => $from, 'to' => $to])
                        ->links() }}
                </div>
            @endif

            <div
                class="stats-load-error"
                role="alert"
                hidden
            ></div>
        </section>
    </div>
</div>

<script>
(() => {
    const panel = document.getElementById('ss-book-statistics');

    if (!panel) {
        return;
    }

    let loading = false;

    panel.addEventListener('click', async (event) => {
        const link = event.target.closest('.stats-pagination a');

        if (
            !link ||
            event.button !== 0 ||
            event.ctrlKey ||
            event.metaKey ||
            event.shiftKey ||
            event.altKey
        ) {
            return;
        }

        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin) {
            return;
        }

        event.preventDefault();

        if (loading) {
            return;
        }

        loading = true;
        url.hash = '';

        const scrollX = window.scrollX;
        const scrollY = window.scrollY;
        const currentHeight = panel.getBoundingClientRect().height;

        panel.style.minHeight = Math.ceil(currentHeight) + 'px';
        panel.setAttribute('aria-busy', 'true');

        const previousError = panel.querySelector('.stats-load-error');

        if (previousError) {
            previousError.hidden = true;
        }

        try {
            const response = await fetch(url.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Không tải được dữ liệu.');
            }

            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(
                html,
                'text/html'
            );

            const nextPanel = nextDocument.getElementById(
                'ss-book-statistics'
            );

            if (!nextPanel) {
                throw new Error('Không tìm thấy bảng thống kê.');
            }

            panel.replaceChildren(...Array.from(nextPanel.childNodes));

            try {
                window.history.replaceState(
                    window.history.state,
                    '',
                    url.pathname + url.search
                );
            } catch (historyError) {
                // Bảng vẫn cập nhật nếu không thể đổi địa chỉ.
            }

            window.scrollTo({
                left: scrollX,
                top: scrollY,
                behavior: 'instant'
            });
        } catch (error) {
            const errorBox = panel.querySelector('.stats-load-error');

            if (errorBox) {
                errorBox.textContent =
                    'Không tải được trang thống kê. Vui lòng thử lại.';
                errorBox.hidden = false;
            }
        } finally {
            loading = false;
            panel.setAttribute('aria-busy', 'false');
        }
    });
})();
</script>
@endsection