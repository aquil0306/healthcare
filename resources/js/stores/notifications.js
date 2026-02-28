import { defineStore } from 'pinia';
import axios from 'axios';

export const useNotificationStore = defineStore('notifications', {
    state: () => ({
        notifications: [],
    }),

    getters: {
        unreadCount: (state) => state.notifications.filter(n => !n.read_at).length,
    },

    actions: {
        async fetchNotifications() {
            try {
                const response = await axios.get('/api/v1/notifications');
                // Handle paginated response structure
                if (response.data.data && response.data.data.data) {
                    this.notifications = response.data.data.data;
                } else if (Array.isArray(response.data.data)) {
                    this.notifications = response.data.data;
                } else {
                    this.notifications = [];
                }
            } catch (error) {
                console.error('Failed to fetch notifications:', error);
                this.notifications = [];
            }
        },

        async acknowledge(notificationId) {
            try {
                await axios.post(`/api/v1/notifications/${notificationId}/acknowledge`);
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification) {
                    notification.read_at = new Date().toISOString();
                }
            } catch (error) {
                console.error('Failed to acknowledge notification:', error);
            }
        },
    },
});

