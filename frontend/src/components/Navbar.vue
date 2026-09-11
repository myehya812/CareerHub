<script setup>
import { RouterLink, useRoute } from 'vue-router';

// Gives us information about the current URL.
// We use it to highlight the active navigation link.
const route = useRoute();

// Return true when the user is currently on this route.
const isActiveLink = (routePath) => {
  return route.path === routePath;
};
</script>

<template>
  <nav class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-20 items-center justify-between">

        <!-- CareerHub Brand -->
        <RouterLink to="/" class="flex items-center gap-3">
         
          -
          <div
            class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg font-black text-white"
          >
            C
          </div>

          <span class="text-2xl font-bold tracking-tight text-slate-900">
            Career<span class="text-indigo-600">Hub</span>
          </span>
        </RouterLink>

        <!-- Navigation -->
        <div class="flex items-center gap-2">

          <RouterLink
            to="/"
            :class="[
              isActiveLink('/')
                ? 'bg-indigo-50 text-indigo-700'
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',

              'rounded-lg px-4 py-2 font-medium transition',
            ]"
          >
            Home
          </RouterLink>

          <RouterLink
            to="/jobs"
            :class="[
              isActiveLink('/jobs')
                ? 'bg-indigo-50 text-indigo-700'
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',

              'rounded-lg px-4 py-2 font-medium transition',
            ]"
          >
            Find Jobs
          </RouterLink>

          <RouterLink
            to="/jobs/add"
            class="ml-2 rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white transition hover:bg-indigo-700"
          >
            Post a Job
          </RouterLink>

        </div>
      </div>
    </div>
  </nav>
</template>