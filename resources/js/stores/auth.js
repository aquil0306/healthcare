import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token'),
    }),

    getters: {
        isAuthenticated: (state) => !!state.token && !!state.user,
    },

    actions: {
        async login(credentials) {
            try {
                const response = await axios.post('/api/v1/auth/login', credentials);
                this.token = response.data.data.token;
                this.user = response.data.data.user;
                localStorage.setItem('token', this.token);
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                return { success: true };
            } catch (error) {
                const errorMessage = error.response?.data?.message || 
                                   error.response?.data?.error || 
                                   'Invalid email or password. Please try again.';
                return { success: false, error: errorMessage };
            }
        },

        async logout() {
            try {
                await axios.post('/api/v1/auth/logout');
            } catch (error) {
                // Continue with logout even if API call fails
            }
            this.token = null;
            this.user = null;
            localStorage.removeItem('token');
            delete axios.defaults.headers.common['Authorization'];
        },

        async fetchUser() {
            try {
                const response = await axios.get('/api/v1/auth/user');
                this.user = response.data.data;
            } catch (error) {
                this.logout();
            }
        },
    },
});

// Set up axios interceptor
axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;

