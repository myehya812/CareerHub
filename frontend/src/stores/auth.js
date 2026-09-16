import { defineStore } from "pinia";
import { computed, ref } from "vue";
import axios from "axios";

export const useAuthStore = defineStore('auth', ()=> {

    const user = ref(null);
    const initialized = ref(false);

    const isAuthenticated = computed(()=> user.value !== null);

    async function fetchUser() {

        try{
            const response = await axios.get('/api/user');
            user.value = response.data;
        }catch(err){
            if(err.response?.status === 401){
                user.value =null;
            }else{
                throw err;
            }
        }finally{
                initialized.value =  true;
        }
        
    }


    async function login(credentials){

        await axios.get('/sanctum/csrf-cookie');
        const response = await axios.post('/api/login' , credentials);
            user.value = response.data.user;
    };

    async function logout() {
        await axios.get('/sanctum/csrf-cookie');

        await axios.post('/api/logout');

        user.value = null;


        
    };


    return{
        user,
        isAuthenticated,
        initialized,
        fetchUser,
        login,
        logout,
    };

});