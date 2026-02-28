<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Notifications</h1>
        
        <!-- Filters -->
        <v-card class="mb-4">
          <v-card-text>
            <v-row>
              <v-col cols="12" md="3">
                <v-text-field
                  v-model="filters.search"
                  label="Search"
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  density="compact"
                  @input="loadNotifications"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="2">
                <v-text-field
                  v-model="filters.staff_id"
                  label="Staff ID"
                  type="number"
                  variant="outlined"
                  density="compact"
                  @input="loadNotifications"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="2">
                <v-text-field
                  v-model="filters.referral_id"
                  label="Referral ID"
                  type="number"
                  variant="outlined"
                  density="compact"
                  @input="loadNotifications"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="2">
                <v-select
                  v-model="filters.channel"
                  label="Channel"
                  :items="channelOptions"
                  variant="outlined"
                  density="compact"
                  clearable
                  @update:model-value="loadNotifications"
                ></v-select>
              </v-col>
              <v-col cols="12" md="2">
                <v-select
                  v-model="filters.type"
                  label="Type"
                  :items="typeOptions"
                  variant="outlined"
                  density="compact"
                  clearable
                  @update:model-value="loadNotifications"
                ></v-select>
              </v-col>
              <v-col cols="12" md="1">
                <v-btn
                  icon="mdi-refresh"
                  variant="outlined"
                  @click="resetFilters"
                  title="Reset Filters"
                ></v-btn>
              </v-col>
            </v-row>
            <v-row>
              <v-col cols="12" md="3">
                <v-select
                  v-model="filters.is_read"
                  label="Read Status"
                  :items="readStatusOptions"
                  variant="outlined"
                  density="compact"
                  clearable
                  @update:model-value="loadNotifications"
                ></v-select>
              </v-col>
              <v-col cols="12" md="2">
                <v-text-field
                  v-model="filters.date_from"
                  label="Date From"
                  type="date"
                  variant="outlined"
                  density="compact"
                  @update:model-value="loadNotifications"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="2">
                <v-text-field
                  v-model="filters.date_to"
                  label="Date To"
                  type="date"
                  variant="outlined"
                  density="compact"
                  @update:model-value="loadNotifications"
                ></v-text-field>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Notifications Table -->
        <v-data-table
          :headers="headers"
          :items="notifications"
          :loading="loading"
          :items-per-page="20"
          :server-items-length="totalItems"
          @click:row="viewNotification"
        >
          <template v-slot:item.channel="{ item }">
            <v-chip
              :color="getChannelColor(item.channel)"
              size="small"
            >
              <v-icon start :icon="getChannelIcon(item.channel)"></v-icon>
              {{ item.channel }}
            </v-chip>
          </template>
          <template v-slot:item.type="{ item }">
            <v-chip
              :color="getTypeColor(item.type)"
              size="small"
              variant="outlined"
            >
              {{ item.type }}
            </v-chip>
          </template>
          <template v-slot:item.staff="{ item }">
            <div v-if="item.staff">
              <div class="font-weight-medium">{{ item.staff.name }}</div>
              <div class="text-caption text-grey">{{ item.staff.email }}</div>
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.referral="{ item }">
            <div v-if="item.referral">
              <v-btn
                variant="text"
                size="small"
                @click.stop="viewReferral(item.referral.id)"
              >
                Referral #{{ item.referral.id }}
              </v-btn>
              <div class="text-caption text-grey">
                {{ item.referral.status }} • {{ item.referral.urgency }}
              </div>
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.is_read="{ item }">
            <v-chip
              :color="item.is_read ? 'success' : 'warning'"
              size="small"
            >
              {{ item.is_read ? 'Read' : 'Unread' }}
            </v-chip>
          </template>
          <template v-slot:item.sent_at="{ item }">
            <div v-if="item.sent_at">
              <div>{{ item.sent_at }}</div>
              <div class="text-caption text-grey" v-if="item.sent_at_raw">
                {{ formatRawDate(item.sent_at_raw) }}
              </div>
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.read_at="{ item }">
            <div v-if="item.read_at">
              <div>{{ item.read_at }}</div>
              <div class="text-caption text-grey" v-if="item.read_at_raw">
                {{ formatRawDate(item.read_at_raw) }}
              </div>
            </div>
            <span v-else class="text-grey">Not read</span>
          </template>
          <template v-slot:item.created_at="{ item }">
            <div>
              <div>{{ item.created_at }}</div>
              <div class="text-caption text-grey" v-if="item.created_at_raw">
                {{ formatRawDate(item.created_at_raw) }}
              </div>
            </div>
          </template>
          <template v-slot:item.actions="{ item }">
            <v-btn
              icon="mdi-eye"
              size="small"
              variant="text"
              @click.stop="viewNotification(null, { item })"
            ></v-btn>
          </template>
        </v-data-table>
      </v-col>
    </v-row>

    <!-- Notification Detail Dialog -->
    <v-dialog v-model="detailDialog" max-width="700">
      <v-card v-if="selectedNotification">
        <v-card-title class="d-flex align-center">
          <v-icon class="mr-2">mdi-bell</v-icon>
          Notification Details
          <v-spacer></v-spacer>
          <v-btn
            icon="mdi-close"
            variant="text"
            size="small"
            @click="detailDialog = false"
          ></v-btn>
        </v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12" md="6">
              <div class="text-caption text-grey mb-1">ID</div>
              <div>{{ selectedNotification.id }}</div>
            </v-col>
            <v-col cols="12" md="6">
              <div class="text-caption text-grey mb-1">Channel</div>
              <v-chip
                :color="getChannelColor(selectedNotification.channel)"
                size="small"
              >
                <v-icon start :icon="getChannelIcon(selectedNotification.channel)"></v-icon>
                {{ selectedNotification.channel }}
              </v-chip>
            </v-col>
            <v-col cols="12" md="6" v-if="selectedNotification.staff">
              <div class="text-caption text-grey mb-1">Staff</div>
              <div>
                <div class="font-weight-medium">{{ selectedNotification.staff.name }}</div>
                <div class="text-caption text-grey">{{ selectedNotification.staff.email }}</div>
              </div>
            </v-col>
            <v-col cols="12" md="6" v-if="selectedNotification.referral">
              <div class="text-caption text-grey mb-1">Referral</div>
              <v-btn
                variant="text"
                size="small"
                @click="viewReferral(selectedNotification.referral.id)"
              >
                Referral #{{ selectedNotification.referral.id }}
              </v-btn>
            </v-col>
            <v-col cols="12">
              <div class="text-caption text-grey mb-1">Message</div>
              <v-card variant="outlined" class="pa-3">
                <div class="text-body-1">{{ selectedNotification.message }}</div>
              </v-card>
            </v-col>
            <v-col cols="12" md="6">
              <div class="text-caption text-grey mb-1">Type</div>
              <v-chip
                :color="getTypeColor(selectedNotification.type)"
                size="small"
                variant="outlined"
              >
                {{ selectedNotification.type }}
              </v-chip>
            </v-col>
            <v-col cols="12" md="6">
              <div class="text-caption text-grey mb-1">Read Status</div>
              <v-chip
                :color="selectedNotification.is_read ? 'success' : 'warning'"
                size="small"
              >
                {{ selectedNotification.is_read ? 'Read' : 'Unread' }}
              </v-chip>
            </v-col>
            <v-col cols="12" md="6" v-if="selectedNotification.sent_at">
              <div class="text-caption text-grey mb-1">Sent At</div>
              <div>{{ selectedNotification.sent_at }}</div>
              <div class="text-caption text-grey" v-if="selectedNotification.sent_at_raw">
                {{ formatRawDate(selectedNotification.sent_at_raw) }}
              </div>
            </v-col>
            <v-col cols="12" md="6" v-if="selectedNotification.read_at">
              <div class="text-caption text-grey mb-1">Read At</div>
              <div>{{ selectedNotification.read_at }}</div>
              <div class="text-caption text-grey" v-if="selectedNotification.read_at_raw">
                {{ formatRawDate(selectedNotification.read_at_raw) }}
              </div>
            </v-col>
            <v-col cols="12" md="6">
              <div class="text-caption text-grey mb-1">Created</div>
              <div>{{ selectedNotification.created_at }}</div>
              <div class="text-caption text-grey" v-if="selectedNotification.created_at_raw">
                {{ formatRawDate(selectedNotification.created_at_raw) }}
              </div>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="detailDialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const notifications = ref([]);
const loading = ref(false);
const totalItems = ref(0);
const detailDialog = ref(false);
const selectedNotification = ref(null);
const filters = ref({
  search: '',
  staff_id: null,
  referral_id: null,
  channel: null,
  type: null,
  is_read: null,
  date_from: '',
  date_to: '',
});

const channelOptions = ['in_app', 'email', 'sms', 'slack'];
const typeOptions = ['referral', 'assignment', 'escalation', 'cancellation'];
const readStatusOptions = [
  { title: 'Read', value: true },
  { title: 'Unread', value: false },
];

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Channel', key: 'channel' },
  { title: 'Type', key: 'type' },
  { title: 'Staff', key: 'staff' },
  { title: 'Referral', key: 'referral' },
  { title: 'Message', key: 'message' },
  { title: 'Read', key: 'is_read' },
  { title: 'Sent At', key: 'sent_at' },
  { title: 'Read At', key: 'read_at' },
  { title: 'Created', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const getChannelColor = (channel) => {
  const colors = {
    in_app: 'primary',
    email: 'info',
    sms: 'success',
    slack: 'purple',
  };
  return colors[channel] || 'grey';
};

const getChannelIcon = (channel) => {
  const icons = {
    in_app: 'mdi-bell',
    email: 'mdi-email',
    sms: 'mdi-message-text',
    slack: 'mdi-slack',
  };
  return icons[channel] || 'mdi-bell';
};

const getTypeColor = (type) => {
  const colors = {
    referral: 'blue',
    assignment: 'purple',
    escalation: 'orange',
    cancellation: 'red',
  };
  return colors[type] || 'grey';
};

const formatRawDate = (dateString) => {
  if (!dateString) return '';
  try {
    const date = new Date(dateString);
    return date.toLocaleString();
  } catch (e) {
    return dateString;
  }
};

const viewNotification = (event, { item }) => {
  if (item) {
    selectedNotification.value = item;
    detailDialog.value = true;
  }
};

const viewReferral = (referralId) => {
  router.push({ name: 'admin.referral.show', params: { id: referralId } });
};

const resetFilters = () => {
  filters.value = {
    search: '',
    staff_id: null,
    referral_id: null,
    channel: null,
    type: null,
    is_read: null,
    date_from: '',
    date_to: '',
  };
  loadNotifications();
};

const loadNotifications = async () => {
  loading.value = true;
  try {
    const params = {};
    if (filters.value.search) params.search = filters.value.search;
    if (filters.value.staff_id) params.staff_id = filters.value.staff_id;
    if (filters.value.referral_id) params.referral_id = filters.value.referral_id;
    if (filters.value.channel) params.channel = filters.value.channel;
    if (filters.value.type) params.type = filters.value.type;
    if (filters.value.is_read !== null) params.is_read = filters.value.is_read;
    if (filters.value.date_from) params.date_from = filters.value.date_from;
    if (filters.value.date_to) params.date_to = filters.value.date_to;

    const response = await axios.get('/api/v1/admin/notifications', { params });
    
    if (response.data.success && response.data.data) {
      const data = response.data.data;
      
      if (Array.isArray(data)) {
        notifications.value = data;
        totalItems.value = data.length;
      } else if (data.data && Array.isArray(data.data)) {
        notifications.value = data.data;
        totalItems.value = data.total || data.data.length;
      } else {
        notifications.value = [];
        totalItems.value = 0;
      }
    } else {
      notifications.value = [];
      totalItems.value = 0;
    }
  } catch (error) {
    console.error('Failed to load notifications:', error);
    notifications.value = [];
    totalItems.value = 0;
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadNotifications();
});
</script>

