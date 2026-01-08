<template>
  <div
    class="relative min-h-screen overflow-hidden bg-gradient-to-br from-slate-50 via-slate-50 to-slate-100 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950"
  >
    <div class="pointer-events-none absolute inset-0 opacity-70">
      <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-blue-400/25 blur-3xl dark:bg-blue-600/25" />
      <div class="absolute -right-28 bottom-10 h-[22rem] w-[22rem] rounded-full bg-purple-400/20 blur-[140px] dark:bg-purple-500/20" />
      <div class="absolute left-1/2 top-1/2 h-[28rem] w-[28rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-cyan-400/10 blur-[180px] dark:bg-cyan-500/10" />
    </div>

    <div class="relative flex min-h-screen items-center justify-center px-5 py-14 sm:px-8 lg:px-12">
      <div class="w-full max-w-md">
        <slot />
      </div>
    </div>
  </div>
</template>

