<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center mb-4">
          <h1 class="text-h4">My Notifications</h1>
          <v-chip v-if="unreadCount > 0" color="error" size="small">
            {{ unreadCount }} unread
          </v-chip>
        </div>

        <v-card v-if="loading" class="pa-4">
          <div class="text-center">
            <v-progress-circular indeterminate color="primary"></v-progress-circular>
            <p class="mt-2">Loading notifications...</p>
          </div>
        </v-card>

        <div v-else-if="notifications.length === 0" class="text-center pa-8">
          <v-icon size="64" color="grey-lighten-1">mdi-bell-off-outline</v-icon>
          <h2 class="text-h6 mt-4 mb-2">No Notifications</h2>
          <p class="text-grey">You will receive notifications when referrals are assigned to you or when your department receives new referrals.</p>
        </div>

        <v-list v-else lines="two" class="bg-transparent">
          <v-list-item
            v-for="notification in notifications"
            :key="notification.id"
            :class="{
              'bg-blue-lighten-5': !notification.read_at,
              'border-left': true,
              'border-primary': !notification.read_at,
              'border-width-4': !notification.read_at
            }"
            class="mb-2 rounded"
            :ripple="false"
          >
            <template v-slot:prepend>
              <v-avatar
                :color="notification.read_at ? 'grey' : 'primary'"
                size="40"
                class="mr-4"
              >
                <v-icon :color="notification.read_at ? 'grey-lighten-1' : 'white'">
                  {{ getNotificationIcon(notification.type) }}
                </v-icon>
              </v-avatar>
            </template>

            <v-list-item-title class="mb-1">
              <span :class="{ 'font-weight-bold': !notification.read_at }">
                {{ notification.message }}
              </span>
            </v-list-item-title>

            <v-list-item-subtitle>
              <div class="d-flex flex-column flex-sm-row gap-2 mt-1">
                <span v-if="notification.referral">
                  <v-chip size="x-small" color="info" variant="outlined" class="mr-1">
                    Referral #{{ notification.referral.id }}
                  </v-chip>
                </span>
                <v-chip
                  size="x-small"
                  :color="getChannelColor(notification.channel)"
                  variant="outlined"
                  class="mr-1"
                >
                  {{ notification.channel }}
                </v-chip>
                <v-chip
                  v-if="notification.type"
                  size="x-small"
                  :color="getTypeColor(notification.type)"
                  variant="outlined"
                  class="mr-1"
                >
                  {{ notification.type }}
                </v-chip>
                <span class="text-caption text-grey">
                  {{ formatDate(notification.created_at) }}
                </span>
              </div>
            </v-list-item-subtitle>

            <template v-slot:append>
              <div class="d-flex align-center gap-2">
                <v-btn
                  v-if="notification.referral"
                  size="small"
                  variant="text"
                  color="primary"
                  :to="{ name: notification.referral.assigned_staff_id ? 'staff.referrals' : 'admin.referrals' }"
                >
                  View Referral
                </v-btn>
                <v-btn
                  v-if="!notification.read_at"
                  size="small"
                  color="primary"
                  variant="flat"
                  @click="acknowledge(notification.id)"
                  :loading="acknowledging === notification.id"
                >
                  Mark as Read
                </v-btn>
                <v-icon
                  v-else
                  color="success"
                  size="small"
                >
                  mdi-check-circle
                </v-icon>
              </div>
            </template>
          </v-list-item>
        </v-list>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useNotificationStore } from '../stores/notifications';

const notificationStore = useNotificationStore();
const notifications = computed(() => notificationStore.notifications);
const unreadCount = computed(() => notificationStore.unreadCount);
const loading = ref(false);
const acknowledging = ref(null);

const acknowledge = async (id) => {
  acknowledging.value = id;
  try {
    await notificationStore.acknowledge(id);
  } finally {
    acknowledging.value = null;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
  if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
  if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
  
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getNotificationIcon = (type) => {
  const icons = {
    assignment: 'mdi-account-plus',
    referral: 'mdi-file-document',
    escalation: 'mdi-alert',
    reminder: 'mdi-bell-ring',
    default: 'mdi-bell'
  };
  return icons[type] || icons.default;
};

const getChannelColor = (channel) => {
  const colors = {
    email: 'blue',
    sms: 'green',
    in_app: 'purple'
  };
  return colors[channel] || 'grey';
};

const getTypeColor = (type) => {
  const colors = {
    assignment: 'primary',
    referral: 'info',
    escalation: 'error',
    reminder: 'warning',
    default: 'grey'
  };
  return colors[type] || colors.default;
};

onMounted(async () => {
  loading.value = true;
  try {
    await notificationStore.fetchNotifications();
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.border-left {
  border-left: 4px solid transparent;
}

.border-width-4 {
  border-left-width: 4px !important;
}
</style>

