<script setup>
import { RouterLink } from 'vue-router';
</script>

<template>
  <!--
    Simple navigation back to the jobs list.
    We use RouterLink so Vue changes pages without reloading the site.
  -->
  <div class="mx-auto max-w-7xl px-6 pt-8">
    <RouterLink
      to="/jobs"
      class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-indigo-600"
    >
      <span>←</span>
      Back to jobs
    </RouterLink>
  </div>
</template>