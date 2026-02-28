<template>
  <v-app>
    <v-app-bar v-if="isAuthenticated" color="primary" prominent>
      <v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
      <v-toolbar-title>Healthcare Referral Management</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn icon @click="logout">
        <v-icon>mdi-logout</v-icon>
      </v-btn>
    </v-app-bar>

    <v-navigation-drawer 
      v-if="isAuthenticated" 
      v-model="drawer"
      :temporary="mobile"
    >
      <v-list>
        <v-list-item v-if="isAdmin" prepend-icon="mdi-view-dashboard" title="Dashboard" :to="{ name: 'admin.dashboard' }"></v-list-item>
        <v-divider v-if="isAdmin"></v-divider>
        <v-list-subheader v-if="isAdmin">Management</v-list-subheader>
        <v-list-item v-if="isAdmin" prepend-icon="mdi-hospital-building" title="Hospitals" :to="{ name: 'admin.hospitals' }"></v-list-item>
        <v-list-item v-if="isAdmin" prepend-icon="mdi-account-multiple" title="Patients" :to="{ name: 'admin.patients' }"></v-list-item>
        <v-list-item v-if="isAdmin" prepend-icon="mdi-account-tie" title="Staff" :to="{ name: 'admin.staff' }"></v-list-item>
        <v-list-item v-if="isAdmin" prepend-icon="mdi-file-document" title="Referrals" :to="{ name: 'admin.referrals' }"></v-list-item>
        <v-divider v-if="isAdmin"></v-divider>
        <v-list-subheader v-if="isAdmin">Access Control</v-list-subheader>
        <v-list-item v-if="isAdmin" prepend-icon="mdi-shield-account" title="Roles" :to="{ name: 'admin.roles' }"></v-list-item>
        <v-list-item v-if="isAdmin" prepend-icon="mdi-shield-key" title="Permissions" :to="{ name: 'admin.permissions' }"></v-list-item>
        <v-divider v-if="isAdmin"></v-divider>
        <v-list-subheader v-if="isAdmin">Reference Data</v-list-subheader>
        <v-list-item v-if="isAdmin" prepend-icon="mdi-code-tags" title="ICD-10 Codes" :to="{ name: 'admin.icd10-codes' }"></v-list-item>
        <v-list-item v-if="isAdmin" prepend-icon="mdi-office-building" title="Departments" :to="{ name: 'admin.departments' }"></v-list-item>
        <v-divider v-if="isAdmin"></v-divider>
        <v-list-item v-if="isAdmin" prepend-icon="mdi-history" title="Audit Logs" :to="{ name: 'admin.audit-logs' }"></v-list-item>
        <v-list-item v-if="isAdmin" prepend-icon="mdi-bell-outline" title="All Notifications" :to="{ name: 'admin.notifications' }"></v-list-item>
        <v-list-item v-if="isAdmin" prepend-icon="mdi-chart-line" title="Reports" :to="{ name: 'admin.reports' }"></v-list-item>
        <v-list-item v-if="isStaff" prepend-icon="mdi-file-document" title="My Referrals" :to="{ name: 'staff.referrals' }"></v-list-item>
        <v-list-item prepend-icon="mdi-bell" title="Notifications" :to="{ name: 'notifications' }">
          <v-badge v-if="unreadCount > 0" :content="unreadCount" color="error" inline>
          </v-badge>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <v-main>
      <router-view />
    </v-main>
  </v-app>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useDisplay } from 'vuetify';
import { useAuthStore } from './stores/auth';
import { useNotificationStore } from './stores/notifications';
import { useRouter } from 'vue-router';

const router = useRouter();
const authStore = useAuthStore();
const notificationStore = useNotificationStore();
const { mobile } = useDisplay();

const drawer = ref(true); // Start with drawer open
const isAuthenticated = computed(() => authStore.isAuthenticated);
const isAdmin = computed(() => authStore.user?.staff?.role === 'admin');
const isStaff = computed(() => ['doctor', 'coordinator'].includes(authStore.user?.staff?.role));
const unreadCount = computed(() => notificationStore.unreadCount);

const logout = async () => {
  await authStore.logout();
  router.push({ name: 'login' });
};

// Load notifications if authenticated
if (isAuthenticated.value) {
  notificationStore.fetchNotifications();
}
</script>

