<script lang="ts">
    export let feeds: Array<{ id: string; title: string; unread?: number }>;
    let q = '';
    $: filtered = q
        ? feeds.filter(f => f.title.toLowerCase().includes(q.toLowerCase()))
        : feeds;
</script>

<div class="p-3 space-y-3">
    <input
            placeholder="Search feeds…"
            bind:value={q}
            class="w-full rounded-lg bg-neutral-900 border border-white/10 px-3 py-2 outline-none focus:border-white/20"
    />

    <nav class="max-h-[calc(100dvh-7rem)] overflow-auto pr-2 sidebar-scroll">
        {#if filtered.length === 0}
            <p class="text-sm text-white/50 px-1">No feeds</p>
        {:else}
            <ul class="space-y-1">
                {#each filtered as f}
                    <li>
                        <a
                                href={`/feeds/${f.id}`}
                                class="flex items-center justify-between rounded-md px-3 py-2 hover:bg-white/5"
                        >
                            <span class="truncate">{f.title}</span>
                            {#if f.unread}
                <span class="ml-2 inline-flex min-w-6 justify-center rounded-full text-xs px-2 py-0.5 bg-white/10">
                  {f.unread}
                </span>
                            {/if}
                        </a>
                    </li>
                {/each}
            </ul>
        {/if}
    </nav>
</div>

<style>
    /* Tiny component-scoped tweak; everything else via Tailwind */
    .sidebar-scroll::-webkit-scrollbar { width: 10px; }
    .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.08); border-radius: 8px; }
    .sidebar-scroll:hover::-webkit-scrollbar-thumb { background: rgba(255,255,255,.16); }
</style>
