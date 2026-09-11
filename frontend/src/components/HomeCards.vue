<script setup>
import { RouterLink } from 'vue-router';
</script>

<template>
  <section class="bg-white py-16">
    <div class="mx-auto max-w-7xl px-6">

      <!-- Section heading -->
      <div class="mb-10 text-center">
        <h2 class="text-3xl font-bold text-slate-900">
          Built for both sides of hiring
        </h2>

        <p class="mt-3 text-slate-600">
          Whether you're looking for your next role or your next team member,
          CareerHub helps you move forward.
        </p>
      </div>

      <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

        <!-- Job Seeker Card -->
        <div
          class="rounded-2xl border border-slate-200 bg-slate-50 p-8 shadow-sm"
        >
          <div
            class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-100 text-xl"
          >
            🔎
          </div>

          <h3 class="text-2xl font-bold text-slate-900">
            Looking for your next role?
          </h3>

          <p class="mt-3 leading-7 text-slate-600">
            Explore opportunities that match your skills, experience, and
            career goals.
          </p>

          <RouterLink
            to="/jobs"
            class="mt-6 inline-flex font-semibold text-indigo-600 hover:text-indigo-700"
          >
            Browse opportunities →
          </RouterLink>
        </div>

        <!-- Employer Card -->
        <div
          class="rounded-2xl border border-indigo-100 bg-indigo-600 p-8 shadow-sm"
        >
          <div
            class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 text-xl"
          >
            👥
          </div>

          <h3 class="text-2xl font-bold text-white">
            Searching for great talent?
          </h3>

          <p class="mt-3 leading-7 text-indigo-100">
            Publish opportunities and connect with candidates who are ready to
            make an impact.
          </p>

          <RouterLink
            to="/jobs/add"
            class="mt-6 inline-flex font-semibold text-white hover:text-indigo-100"
          >
            Post an opportunity →
          </RouterLink>
        </div>

      </div>
    </div>
  </section>
</template>