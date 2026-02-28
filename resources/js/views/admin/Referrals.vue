<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center mb-4">
          <h1 class="text-h4">Referrals</h1>
          <div class="d-flex gap-2">
            <v-btn
              icon="mdi-view-column"
              variant="outlined"
              @click="columnDialog = true"
              title="Customize Columns"
            ></v-btn>
          </div>
        </div>
        <v-data-table
          :headers="visibleHeaders"
          :items="referrals"
          :loading="loading"
          :items-per-page="15"
          :server-items-length="totalItems"
          @click:row="viewReferral"
        >
          <template v-slot:item.urgency="{ item }">
            <v-chip :color="getUrgencyColor(item.urgency)" size="small">
              {{ item.urgency }}
            </v-chip>
          </template>
          <template v-slot:item.status="{ item }">
            <v-chip :color="getStatusColor(item.status)" size="small">
              {{ item.status }}
            </v-chip>
          </template>
          <template v-slot:item.patient="{ item }">
            <div v-if="item.patient">
              <div class="font-weight-medium">{{ item.patient.first_name }} {{ item.patient.last_name }}</div>
              <div class="text-caption text-grey">DOB: {{ formatDate(item.patient.date_of_birth) }}</div>
              <div class="text-caption text-grey">ID: {{ item.patient.national_id }}</div>
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.hospital="{ item }">
            <div v-if="item.hospital">
              <div class="font-weight-medium">{{ item.hospital.name }}</div>
              <div class="text-caption text-grey">{{ item.hospital.code }}</div>
              <v-chip
                :color="item.hospital.status === 'active' ? 'success' : 'error'"
                size="x-small"
                class="mt-1"
              >
                {{ item.hospital.status }}
              </v-chip>
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.department="{ item }">
            <div v-if="item.department_resource">
              <div class="font-weight-medium">{{ item.department_resource.name }}</div>
              <div class="text-caption text-grey" v-if="item.department_resource.code">{{ item.department_resource.code }}</div>
            </div>
            <span v-else-if="item.department">{{ item.department }}</span>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.icd10_codes="{ item }">
            <div v-if="item.icd10_codes && item.icd10_codes.length > 0">
              <v-chip
                v-for="code in item.icd10_codes.slice(0, 3)"
                :key="code.id"
                size="small"
                color="primary"
                variant="outlined"
                class="ma-1"
              >
                {{ code.code }}
              </v-chip>
              <v-chip
                v-if="item.icd10_codes.length > 3"
                size="small"
                color="grey"
                variant="text"
              >
                +{{ item.icd10_codes.length - 3 }} more
              </v-chip>
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.clinical_notes="{ item }">
            <div v-if="item.clinical_notes" class="text-truncate" style="max-width: 250px;" :title="item.clinical_notes">
              {{ item.clinical_notes }}
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.ai_confidence_score="{ item }">
            <div v-if="item.ai_confidence_score !== null && item.ai_confidence_score !== undefined">
              <v-chip
                :color="getConfidenceColor(item.ai_confidence_score)"
                size="small"
              >
                {{ (item.ai_confidence_score * 100).toFixed(1) }}%
              </v-chip>
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.processed_at="{ item }">
            <div v-if="item.processed_at">
              <div>{{ formatDate(item.processed_at) }}</div>
              <div class="text-caption text-grey" v-if="item.processed_at_raw">
                {{ formatRawDate(item.processed_at_raw) }}
              </div>
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.assigned_staff="{ item }">
            <div v-if="item.assigned_staff">
              <div class="font-weight-medium">{{ item.assigned_staff.name }}</div>
              <div class="text-caption text-grey">{{ item.assigned_staff.email }}</div>
              <v-chip
                v-if="item.assigned_staff.department"
                size="x-small"
                color="info"
                class="mt-1"
              >
                {{ item.assigned_staff.department }}
              </v-chip>
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.external_referral_id="{ item }">
            <code class="text-caption">{{ item.external_referral_id || '-' }}</code>
          </template>
          <template v-slot:item.cancellation_reason="{ item }">
            <div v-if="item.cancellation_reason" class="text-truncate" style="max-width: 200px;" :title="item.cancellation_reason">
              {{ item.cancellation_reason }}
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.acknowledged_at="{ item }">
            <div v-if="item.acknowledged_at">
              <div>{{ formatDate(item.acknowledged_at) }}</div>
              <div class="text-caption text-grey" v-if="item.acknowledged_at_raw">
                {{ formatRawDate(item.acknowledged_at_raw) }}
              </div>
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.created_at="{ item }">
            <div>
              <div>{{ formatDate(item.created_at) }}</div>
              <div class="text-caption text-grey" v-if="item.created_at_raw">
                {{ formatRawDate(item.created_at_raw) }}
              </div>
            </div>
          </template>
          <template v-slot:item.updated_at="{ item }">
            <div v-if="item.updated_at">
              <div>{{ formatDate(item.updated_at) }}</div>
              <div class="text-caption text-grey" v-if="item.updated_at_raw">
                {{ formatRawDate(item.updated_at_raw) }}
              </div>
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          <template v-slot:item.actions="{ item }">
            <v-menu>
              <template v-slot:activator="{ props }">
                <v-btn
                  icon="mdi-dots-vertical"
                  size="small"
                  v-bind="props"
                  @click.stop
                ></v-btn>
              </template>
              <v-list>
                <v-list-item @click.stop="viewReferral(null, { item })">
                  <template v-slot:prepend>
                    <v-icon>mdi-eye</v-icon>
                  </template>
                  <v-list-item-title>View</v-list-item-title>
                </v-list-item>
                <v-list-item
                  v-if="item.status !== 'completed' && item.status !== 'cancelled'"
                  @click.stop="openAssignDialog(item)"
                >
                  <template v-slot:prepend>
                    <v-icon>mdi-account-plus</v-icon>
                  </template>
                  <v-list-item-title>Assign to Staff</v-list-item-title>
                </v-list-item>
                <v-list-item
                  v-if="item.status !== 'completed' && item.status !== 'cancelled'"
                  @click.stop="openCancelDialog(item)"
                >
                  <template v-slot:prepend>
                    <v-icon color="error">mdi-cancel</v-icon>
                  </template>
                  <v-list-item-title class="text-error">Cancel Referral</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </template>
        </v-data-table>
      </v-col>
    </v-row>

    <!-- Column Visibility Dialog -->
    <v-dialog v-model="columnDialog" max-width="500">
      <v-card>
        <v-card-title>
          <span>Customize Columns</span>
          <v-spacer></v-spacer>
          <v-btn
            icon="mdi-close"
            variant="text"
            size="small"
            @click="columnDialog = false"
          ></v-btn>
        </v-card-title>
        <v-card-text>
          <v-list>
            <v-list-item
              v-for="header in allHeaders"
              :key="header.key"
            >
              <template v-slot:prepend>
                <v-checkbox
                  v-model="header.visible"
                  hide-details
                  @update:model-value="saveColumnPreferences"
                ></v-checkbox>
              </template>
              <v-list-item-title>{{ header.title }}</v-list-item-title>
            </v-list-item>
          </v-list>
          <v-divider class="my-3"></v-divider>
          <div class="d-flex gap-2">
            <v-btn
              size="small"
              variant="outlined"
              @click="showAllColumns"
            >
              Show All
            </v-btn>
            <v-btn
              size="small"
              variant="outlined"
              @click="showDefaultColumns"
            >
              Reset to Default
            </v-btn>
          </div>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Assign Staff Dialog -->
    <v-dialog v-model="assignDialog" max-width="500">
      <v-card>
        <v-card-title>Assign Referral to Staff</v-card-title>
        <v-card-text>
          <v-select
            v-model="selectedStaffId"
            label="Select Staff Member"
            :items="availableStaff"
            item-title="name"
            item-value="id"
            :loading="loadingStaff"
            return-object
          ></v-select>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="assignDialog = false">Cancel</v-btn>
          <v-btn color="primary" :disabled="!selectedStaffId" @click="assignReferral">
            Assign
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Cancel Referral Dialog -->
    <v-dialog v-model="cancelDialog" max-width="500">
      <v-card>
        <v-card-title>Cancel Referral</v-card-title>
        <v-card-text>
          <p class="mb-2">
            Please provide a reason for cancelling this referral.
          </p>
          <v-textarea
            v-model="cancelReason"
            label="Cancellation Reason"
            auto-grow
            rows="3"
            :counter="500"
            required
          ></v-textarea>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="cancelDialog = false">Close</v-btn>
          <v-btn
            color="error"
            :disabled="!cancelReason.trim()"
            :loading="cancelling"
            @click="cancelReferral"
          >
            Confirm Cancel
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const referrals = ref([]);
const loading = ref(false);
const totalItems = ref(0);
const assignDialog = ref(false);
const cancelDialog = ref(false);
const columnDialog = ref(false);
const selectedReferral = ref(null);
const selectedStaffId = ref(null);
const availableStaff = ref([]);
const loadingStaff = ref(false);
const cancelReason = ref('');
const cancelling = ref(false);

const STORAGE_KEY = 'referrals_column_preferences';

// All available headers with default visibility
const allHeaders = ref([
  { title: 'ID', key: 'id', visible: true },
  { title: 'Patient', key: 'patient', visible: true },
  { title: 'Hospital', key: 'hospital', visible: true },
  { title: 'Urgency', key: 'urgency', visible: true },
  { title: 'Status', key: 'status', visible: true },
  { title: 'Department', key: 'department', visible: false },
  { title: 'ICD-10 Codes', key: 'icd10_codes', visible: false },
  { title: 'Clinical Notes', key: 'clinical_notes', visible: false },
  { title: 'AI Confidence', key: 'ai_confidence_score', visible: false },
  { title: 'Processed At', key: 'processed_at', visible: false },
  { title: 'Assigned To', key: 'assigned_staff', visible: false },
  { title: 'External Referral ID', key: 'external_referral_id', visible: false },
  { title: 'Cancellation Reason', key: 'cancellation_reason', visible: false },
  { title: 'Acknowledged At', key: 'acknowledged_at', visible: false },
  { title: 'Created', key: 'created_at', visible: false },
  { title: 'Updated', key: 'updated_at', visible: false },
  { title: 'Actions', key: 'actions', visible: true, sortable: false },
]);

// Computed property for visible headers only
const visibleHeaders = computed(() => {
  return allHeaders.value.filter(h => h.visible);
});

// Load column preferences from localStorage
const loadColumnPreferences = () => {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
      const preferences = JSON.parse(saved);
      allHeaders.value.forEach(header => {
        if (preferences[header.key] !== undefined) {
          header.visible = preferences[header.key];
        }
      });
    } else {
      // First time - show first 5 columns by default
      allHeaders.value.forEach((header, index) => {
        header.visible = index < 5 || header.key === 'actions';
      });
      saveColumnPreferences();
    }
  } catch (e) {
    console.error('Failed to load column preferences:', e);
  }
};

// Save column preferences to localStorage
const saveColumnPreferences = () => {
  try {
    const preferences = {};
    allHeaders.value.forEach(header => {
      preferences[header.key] = header.visible;
    });
    localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences));
  } catch (e) {
    console.error('Failed to save column preferences:', e);
  }
};

// Show all columns
const showAllColumns = () => {
  allHeaders.value.forEach(header => {
    if (header.key !== 'actions') {
      header.visible = true;
    }
  });
  saveColumnPreferences();
};

// Reset to default (first 5 columns)
const showDefaultColumns = () => {
  allHeaders.value.forEach((header, index) => {
    header.visible = index < 5 || header.key === 'actions';
  });
  saveColumnPreferences();
};

const getUrgencyColor = (urgency) => {
  const colors = { emergency: 'red', urgent: 'orange', routine: 'blue' };
  return colors[urgency] || 'grey';
};

const getStatusColor = (status) => {
  const colors = {
    submitted: 'grey',
    triaged: 'blue',
    assigned: 'purple',
    acknowledged: 'green',
    completed: 'success',
    cancelled: 'red',
  };
  return colors[status] || 'grey';
};

const getConfidenceColor = (score) => {
  if (score >= 0.8) return 'success';
  if (score >= 0.6) return 'warning';
  return 'error';
};

// Dates are now humanized from the API
const formatDate = (dateString) => {
  return dateString || '-';
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

const viewReferral = (event, { item }) => {
  if (item) {
    router.push({ name: 'admin.referral.show', params: { id: item.id } });
  }
};

const openAssignDialog = async (referral) => {
  selectedReferral.value = referral;
  selectedStaffId.value = referral.assigned_staff_id || null;
  loadingStaff.value = true;
  assignDialog.value = true;
  
  try {
    const response = await axios.get('/api/v1/admin/staff');
    if (response.data.success && response.data.data) {
      // Handle both direct array and paginated structure
      const staffData = Array.isArray(response.data.data) 
        ? response.data.data 
        : (response.data.data.data || []);
      availableStaff.value = staffData.filter(s => s.is_available);
    }
  } catch (error) {
    console.error('Failed to load staff:', error);
    availableStaff.value = [];
  } finally {
    loadingStaff.value = false;
  }
};

const assignReferral = async () => {
  if (!selectedReferral.value || !selectedStaffId.value) return;
  
  try {
    await axios.post(`/api/v1/admin/referrals/${selectedReferral.value.id}/assign`, {
      staff_id: selectedStaffId.value.id || selectedStaffId.value,
    });
    assignDialog.value = false;
    loadReferrals();
  } catch (error) {
    console.error('Failed to assign referral:', error);
    alert(error.response?.data?.message || 'Failed to assign referral');
  }
};

const openCancelDialog = (referral) => {
  selectedReferral.value = referral;
  cancelReason.value = '';
  cancelDialog.value = true;
};

const cancelReferral = async () => {
  if (!selectedReferral.value || !cancelReason.value.trim()) return;

  cancelling.value = true;
  try {
    await axios.post(`/api/v1/admin/referrals/${selectedReferral.value.id}/cancel`, {
      reason: cancelReason.value,
    });
    cancelDialog.value = false;
    loadReferrals();
  } catch (error) {
    console.error('Failed to cancel referral:', error);
    alert(error.response?.data?.message || 'Failed to cancel referral');
  } finally {
    cancelling.value = false;
  }
};

const loadReferrals = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/admin/referrals');
    console.log('Referrals API Response:', response.data);
    
    if (response.data.success && response.data.data) {
      // Handle paginated response structure
      const data = response.data.data;
      
      if (Array.isArray(data)) {
        // Direct array response from API Resource collection
        referrals.value = data.map(r => ({
          ...r,
          assigned_staff: r.assigned_staff || null,
          department_string: r.department || null, // Keep old department string for backward compatibility
        }));
        totalItems.value = data.length;
      } else if (data.data && Array.isArray(data.data)) {
        // Paginated response: { data: { data: [...], current_page, total, etc } }
        referrals.value = data.data.map(r => ({
          ...r,
          assigned_staff: r.assigned_staff || null,
          department_string: r.department || null,
        }));
        totalItems.value = data.total || data.data.length;
      } else {
        referrals.value = [];
        totalItems.value = 0;
      }
      
      console.log('Loaded referrals:', referrals.value.length, 'Total:', totalItems.value);
    } else {
      console.error('Unexpected response structure:', response.data);
      referrals.value = [];
      totalItems.value = 0;
    }
  } catch (error) {
    console.error('Failed to load referrals:', error);
    if (error.response) {
      console.error('Response error:', error.response.data);
      console.error('Status:', error.response.status);
      if (error.response.status === 403) {
        alert('You do not have permission to view referrals.');
      } else {
        alert('Failed to load referrals. Please check console for details.');
      }
    } else {
      alert('Failed to load referrals. Please check console for details.');
    }
    referrals.value = [];
    totalItems.value = 0;
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadColumnPreferences();
  loadReferrals();
});
</script>

<style scoped>
.gap-2 {
  gap: 0.5rem;
}
</style>
