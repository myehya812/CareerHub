<script setup>

import { ref , onMounted} from 'vue'
import axios from 'axios';

const message = ref('Loading...');
const error = ref('');

onMounted(async () => {
  try {
    const response = await axios.get('http://127.0.0.1:8000/api/test');

    message.value = response.data.message;
  } catch (error) {
    error.value = 'Could not connect to Laravel';
    console.error(error);
  }
});

</script>



<template>
    <section class="p-6">
        <h1 class="text-2x1 font-bold mb-4">Laravel Test LAL API</h1>

        <p v-if="error">{{ error }}</p>

        <p v-else>{{ message }} </p>


    </section>


</template>