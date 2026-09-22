<script setup>
import { RouterLink, useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import { useAuthStore } from "@/stores/auth";

const toast = useToast();

const route = useRoute();

const router = useRouter();

const auth = useAuthStore();

const isActiveLink = (routePath) => {
  return route.path === routePath;
};

async function handleLogout() {
  try {
    await auth.logout();

    toast.success("Logged out successfully.");

    router.push("/");
  } catch (err) {
    toast.error("Unable to log out. Please try again later.");
  }
}
</script>

<template>
  <nav class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="flex h-20 items-center justify-between">
        <RouterLink to="/" class="flex items-center gap-4">
          <div
            class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg font-black text-white">
            C
          </div>

          <span class="text-2xl pr-2 font-bold tracking-tight text-slate-900">
            Career<span class="text-indigo-600">Hub</span>
          </span>
        </RouterLink>

        <div class="flex items-center gap-2">
          <RouterLink to="/" :class="[
            isActiveLink('/')
              ? 'bg-indigo-50 text-indigo-700'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',

            'rounded-lg px-5 py-2 font-medium transition',
          ]">
            Home
          </RouterLink>

          <RouterLink to="/jobs" :class="[
            isActiveLink('/jobs')
              ? 'bg-indigo-50 text-indigo-700'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',

            'rounded-lg px-4 py-2 font-medium transition',
          ]">
            Find Jobs
          </RouterLink>

          <RouterLink v-if="auth.isAuthenticated && auth.user?.role === 'job_seeker'" to="/applications" :class="[
            isActiveLink('/applications')
              ? 'bg-indigo-50 text-indigo-700'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',

            'rounded-lg px-4 py-2 font-medium transition',
          ]">
            My Applications
          </RouterLink>

            <RouterLink v-if="auth.isAuthenticated && auth.user?.role === 'job_seeker'"
            to="/saved-jobs"
            :class="[
              isActiveLink('/saved-jobs')
      ? 'bg-indigo-50 text-indigo-700'
      : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
            'rounded-lg px-4 py-2 font-medium transition', ]">
            
          
            Saved Jobs
          </RouterLink>


          <RouterLink v-if="auth.isAuthenticated && auth.user?.role === 'company'" to="/jobs/add"
            class="ml-2 rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white transition hover:bg-indigo-700">
            Post a Job
          </RouterLink>



          <template v-if="!auth.isAuthenticated">
            <RouterLink to="/login"
              class="ml-2 rounded-lg px-4 py-2 font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
              Login
            </RouterLink>

            <RouterLink to="/register"
              class="rounded-lg border border-indigo-600 px-4 py-2 font-semibold text-indigo-600 transition hover:bg-indigo-50">
              Register
            </RouterLink>
          </template>

          <template v-else>
            <span class="ml-3 font-medium text-slate-700">
              {{ auth.user.name }}
            </span>

            <button @click="handleLogout"
              class="rounded-lg border border-slate-300 px-4 py-2 font-medium text-slate-700 transition hover:bg-slate-100">
              Logout
            </button>
          </template>
        </div>
      </div>
    </div>
  </nav>
</template>