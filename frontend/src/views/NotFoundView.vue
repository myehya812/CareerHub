<script setup>
import { RouterLink } from 'vue-router';
</script>

<template>
  <section class="flex min-h-[70vh] items-center justify-center px-6 py-20">
    <div class="max-w-xl text-center">

      <p class="text-sm font-bold uppercase tracking-widest text-indigo-600">
        Error 404
      </p>

      <h1 class="mt-4 text-5xl font-black tracking-tight text-slate-900">
        Page not found
      </h1>

      <p class="mt-5 text-lg leading-8 text-slate-600">
        The page you're looking for may have been moved, removed, or never
        existed.
      </p>

      <div class="mt-8 flex justify-center gap-3">
        <RouterLink
          to="/"
          class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white transition hover:bg-indigo-700"
        >
          Go Home
        </RouterLink>

        <RouterLink
          to="/jobs"
          class="rounded-xl border border-slate-300 bg-white px-6 py-3 font-semibold text-slate-700 transition hover:bg-slate-50"
        >
          Browse Jobs
        </RouterLink>
      </div>

    </div>
  </section>
</template>