<script setup>
import { useAuthStore } from "@/stores/auth";
import JobSeekerDashboardView from "./JobSeekerDashboardView.vue";
import CompanyDashboardView from "./CompanyDashboardView.vue";

const auth = useAuthStore();
</script>

<template>
    <JobSeekerDashboardView v-if="auth.user?.role === 'job_seeker'" />

    <CompanyDashboardView v-if="auth.user?.role === 'company'" />

    <p v-else class="p-10 text-center text-slate-600 ">
        Dashboard unavailable.
    </p>
</template> 