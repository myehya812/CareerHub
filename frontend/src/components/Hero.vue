<script setup>
import { defineProps } from 'vue';
import { RouterLink } from 'vue-router';

defineProps({
  title: {
    type: String,

    default: 'Find the right opportunity for your next move.',
  },

  subtitle: {
    type: String,

    default:
      'Discover jobs, explore growing companies, and take the next step in your career.',
  },
});
</script>

<template>
  <section
    class="border-b border-slate-200 bg-gradient-to-br from-slate-50 via-white to-indigo-50"
  >
    <div
      class="mx-auto flex max-w-7xl flex-col items-center px-6 py-24 text-center lg:py-32"
    >

      <div
        class="mb-6 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700"
      >
        Build your career with confidence
      </div>

      <h1
        class="max-w-4xl text-4xl font-black tracking-tight text-slate-900 sm:text-5xl md:text-6xl"
      >
        {{ title }}
      </h1>

      <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
        {{ subtitle }}
      </p>

      <div class="mt-8 flex flex-col gap-3 sm:flex-row">

        <RouterLink
          to="/jobs"
          class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white shadow-sm transition hover:bg-indigo-700"
        >
          Explore Jobs
        </RouterLink>

        <RouterLink
          to="/jobs/add"
          class="rounded-xl border border-slate-300 bg-white px-6 py-3 font-semibold text-slate-700 transition hover:bg-slate-50"
        >
          Post an Opportunity
        </RouterLink>

      </div>
    </div>
  </section>
</template>