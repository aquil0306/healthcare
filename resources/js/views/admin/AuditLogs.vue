<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Audit Logs</h1>
        
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
                  @input="loadAuditLogs"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="2">
                <v-text-field
                  v-model="filters.referral_id"
                  label="Referral ID"
                  type="number"
                  variant="outlined"
                  density="compact"
                  @input="loadAuditLogs"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="2">
                <v-select
                  v-model="filters.action"
                  label="Action"
                  :items="actionOptions"
                  variant="outlined"
                  density="compact"
                  clearable
                  @update:model-value="loadAuditLogs"
                ></v-select>
              </v-col>
              <v-col cols="12" md="2">
                <v-text-field
                  v-model="filters.date_from"
                  label="Date From"
                  type="date"
                  variant="outlined"
                  density="compact"
                  @update:model-value="loadAuditLogs"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="2">
                <v-text-field
                  v-model="filters.date_to"
                  label="Date To"
                  type="date"
                  variant="outlined"
                  density="compact"
                  @update:model-value="loadAuditLogs"
                ></v-text-field>
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
          </v-card-text>
        </v-card>

        <!-- Audit Logs Table -->
        <v-data-table
          :headers="headers"
          :items="auditLogs"
          :loading="loading"
          :items-per-page="20"
          :server-items-length="totalItems"
          @click:row="viewAuditLog"
        >
          <template v-slot:item.action="{ item }">
            <v-chip
              :color="getActionColor(item.action)"
              size="small"
            >
              {{ item.action }}
            </v-chip>
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
                Status: {{ item.referral.status }} • Urgency: {{ item.referral.urgency }}
              </div>
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.user="{ item }">
            <div v-if="item.user">
              <div class="font-weight-medium">{{ item.user.name }}</div>
              <div class="text-caption text-grey">{{ item.user.email }}</div>
            </div>
            <span v-else class="text-grey">System</span>
          </template>
          <template v-slot:item.field="{ item }">
            <code v-if="item.field" class="text-caption">{{ item.field }}</code>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.old_value="{ item }">
            <div v-if="item.old_value !== null" class="text-truncate" style="max-width: 150px;" :title="formatValue(item.old_value)">
              <span class="text-error">{{ formatValue(item.old_value) }}</span>
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.new_value="{ item }">
            <div v-if="item.new_value !== null" class="text-truncate" style="max-width: 150px;" :title="formatValue(item.new_value)">
              <span class="text-success">{{ formatValue(item.new_value) }}</span>
            </div>
            <span v-else class="text-grey">-</span>
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
              @click.stop="viewAuditLog(null, { item })"
            ></v-btn>
          </template>
        </v-data-table>
      </v-col>
    </v-row>

    <!-- Audit Log Detail Dialog -->
    <v-dialog v-model="detailDialog" max-width="800">
      <v-card v-if="selectedLog">
        <v-card-title class="d-flex align-center">
          <v-icon class="mr-2">mdi-information</v-icon>
          Audit Log Details
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
              <div>{{ selectedLog.id }}</div>
            </v-col>
            <v-col cols="12" md="6">
              <div class="text-caption text-grey mb-1">Action</div>
              <v-chip
                :color="getActionColor(selectedLog.action)"
                size="small"
              >
                {{ selectedLog.action }}
              </v-chip>
            </v-col>
            <v-col cols="12" md="6" v-if="selectedLog.referral">
              <div class="text-caption text-grey mb-1">Referral</div>
              <v-btn
                variant="text"
                size="small"
                @click="viewReferral(selectedLog.referral.id)"
              >
                Referral #{{ selectedLog.referral.id }}
              </v-btn>
            </v-col>
            <v-col cols="12" md="6" v-if="selectedLog.user">
              <div class="text-caption text-grey mb-1">User</div>
              <div>
                <div class="font-weight-medium">{{ selectedLog.user.name }}</div>
                <div class="text-caption text-grey">{{ selectedLog.user.email }}</div>
              </div>
            </v-col>
            <v-col cols="12" md="6" v-if="selectedLog.field">
              <div class="text-caption text-grey mb-1">Field</div>
              <code>{{ selectedLog.field }}</code>
            </v-col>
            <v-col cols="12" md="6">
              <div class="text-caption text-grey mb-1">Created At</div>
              <div>{{ selectedLog.created_at }}</div>
              <div class="text-caption text-grey" v-if="selectedLog.created_at_raw">
                {{ formatRawDate(selectedLog.created_at_raw) }}
              </div>
            </v-col>
            <v-col cols="12" v-if="selectedLog.old_value !== null || selectedLog.new_value !== null">
              <div class="text-caption text-grey mb-2">Value Changes</div>
              <v-card variant="outlined">
                <v-card-text>
                  <div class="mb-2">
                    <span class="text-caption text-grey">Old Value:</span>
                    <div class="mt-1">
                      <code v-if="selectedLog.old_value !== null">{{ formatValue(selectedLog.old_value) }}</code>
                      <span v-else class="text-grey">N/A</span>
                    </div>
                  </div>
                  <v-divider class="my-2"></v-divider>
                  <div>
                    <span class="text-caption text-grey">New Value:</span>
                    <div class="mt-1">
                      <code v-if="selectedLog.new_value !== null">{{ formatValue(selectedLog.new_value) }}</code>
                      <span v-else class="text-grey">N/A</span>
                    </div>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
            <v-col cols="12" v-if="selectedLog.metadata">
              <div class="text-caption text-grey mb-1">Metadata</div>
              <v-card variant="outlined" class="pa-2">
                <pre class="text-caption">{{ JSON.stringify(selectedLog.metadata, null, 2) }}</pre>
              </v-card>
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
const auditLogs = ref([]);
const loading = ref(false);
const totalItems = ref(0);
const detailDialog = ref(false);
const selectedLog = ref(null);
const filters = ref({
  search: '',
  referral_id: null,
  action: null,
  date_from: '',
  date_to: '',
});

const actionOptions = [
  'assigned',
  'cancelled',
  'acknowledged',
  'updated',
  'created',
  'escalated',
  'status_changed',
];

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Action', key: 'action' },
  { title: 'Referral', key: 'referral' },
  { title: 'User', key: 'user' },
  { title: 'Field', key: 'field' },
  { title: 'Old Value', key: 'old_value' },
  { title: 'New Value', key: 'new_value' },
  { title: 'Created', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const getActionColor = (action) => {
  const colors = {
    assigned: 'purple',
    cancelled: 'red',
    acknowledged: 'green',
    updated: 'blue',
    created: 'success',
    escalated: 'orange',
    status_changed: 'info',
  };
  return colors[action] || 'grey';
};

const formatValue = (value) => {
  if (value === null || value === undefined) return 'N/A';
  if (typeof value === 'object') return JSON.stringify(value);
  return String(value);
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

const viewAuditLog = (event, { item }) => {
  if (item) {
    selectedLog.value = item;
    detailDialog.value = true;
  }
};

const viewReferral = (referralId) => {
  router.push({ name: 'admin.referral.show', params: { id: referralId } });
};

const resetFilters = () => {
  filters.value = {
    search: '',
    referral_id: null,
    action: null,
    date_from: '',
    date_to: '',
  };
  loadAuditLogs();
};

const loadAuditLogs = async () => {
  loading.value = true;
  try {
    const params = {};
    if (filters.value.search) params.search = filters.value.search;
    if (filters.value.referral_id) params.referral_id = filters.value.referral_id;
    if (filters.value.action) params.action = filters.value.action;
    if (filters.value.date_from) params.date_from = filters.value.date_from;
    if (filters.value.date_to) params.date_to = filters.value.date_to;

    const response = await axios.get('/api/v1/admin/audit-logs', { params });
    
    if (response.data.success && response.data.data) {
      const data = response.data.data;
      
      if (Array.isArray(data)) {
        auditLogs.value = data;
        totalItems.value = data.length;
      } else if (data.data && Array.isArray(data.data)) {
        auditLogs.value = data.data;
        totalItems.value = data.total || data.data.length;
      } else {
        auditLogs.value = [];
        totalItems.value = 0;
      }
    } else {
      auditLogs.value = [];
      totalItems.value = 0;
    }
  } catch (error) {
    console.error('Failed to load audit logs:', error);
    auditLogs.value = [];
    totalItems.value = 0;
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadAuditLogs();
});
</script>

