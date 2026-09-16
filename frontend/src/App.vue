<script setup>
import { onMounted } from 'vue';
import Navbar from '@/components/Navbar.vue';
import { RouterView } from 'vue-router';
import { useAuthStore } from './stores/auth';

const auth = useAuthStore();

onMounted(() => {
  auth.fetchUser();
});

</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
  <Navbar />
  <RouterView />

  </div>
</template>
