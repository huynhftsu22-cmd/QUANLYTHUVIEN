<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
@switch($icon)
    @case('book')<path d="M12 6c-3-2-6-2-9-1v15c3-1 6-1 9 1 3-2 6-2 9-1V5c-3-1-6-1-9 1Z"/><path d="M12 6v15M6 9h3M15 9h3"/>@break
    @case('overview')<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><path d="M14 21v-4m3 4V13m4 8v-6"/>@break
    @case('search')<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>@break
    @case('pen')<path d="m14 5 5 5M4 20l5-1L21 7a2.1 2.1 0 0 0-5-5L4 14l-1 7 6-2"/>@break
    @case('grid')<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>@break
    @case('users')<circle cx="9" cy="8" r="3"/><path d="M3 21v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 4v2"/>@break
    @case('card')<rect x="3" y="5" width="18" height="14" rx="3"/><circle cx="8" cy="11" r="2"/><path d="M5 16a3 3 0 0 1 6 0m3-6h4m-4 4h3"/>@break
    @case('shield')<path d="m12 3 8 3v6c0 4-4 7-8 9-4-2-8-5-8-9V6Z"/><path d="m8 12 3 3 5-6"/>@break
    @case('out')<path d="M14 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h9M9 12h12m-4-4 4 4-4 4"/>@break
    @case('in')<path d="M10 4h9a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-9M15 12H3m4-4-4 4 4 4"/>@break
    @case('wallet')<path d="M20 8V6a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h15v12H5a2 2 0 0 1-2-2V6"/><path d="M21 12h-5v4h5m-3-2h.01"/>@break
    @case('history')<path d="M3 10a9 9 0 1 1 2 8M3 4v6h6m3-3v6l4 2"/>@break
    @default<path d="M4 6h16M4 12h16M4 18h16"/>
@endswitch
</svg>
