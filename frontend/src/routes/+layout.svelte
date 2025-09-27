<script lang="ts">
  import '../app.css';
  import SidebarFeeds from '$lib/components/SidebarFeeds.svelte';
  import favicon from '$lib/assets/favicon.svg';

  export let data: { feeds: Array<{ id: number; title: string; read: boolean }> };
</script>

<svelte:head>
  <title>FeedNews 2026</title>
  <link rel="icon" href={favicon} />
</svelte:head>


<div class="grid min-h-dvh grid-cols-[300px_1fr]">
    <aside class="border-r border/10">
        <SidebarFeeds feeds={data.feeds} />
    </aside>
    <main class="min-w-0">
        <slot />
    </main>
</div>