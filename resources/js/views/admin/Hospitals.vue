<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center mb-4">
          <h1 class="text-h4">Hospitals</h1>
          <v-btn color="primary" @click="openDialog()">
            <v-icon start>mdi-plus</v-icon>
            Add Hospital
          </v-btn>
        </div>
        <v-data-table
          :headers="headers"
          :items="hospitals"
          :loading="loading"
          @click:row="onRowClickHospital"
        >
          <template v-slot:item.status="{ item }">
            <v-chip :color="item.status === 'active' ? 'success' : 'error'" small>
              {{ item.status }}
            </v-chip>
          </template>
          <template v-slot:item.actions="{ item }">
            <v-menu>
              <template v-slot:activator="{ props }">
                <v-btn icon="mdi-dots-vertical" size="small" v-bind="props" @click.stop></v-btn>
              </template>
              <v-list>
                <v-list-item @click="editHospital(item)">
                  <template v-slot:prepend>
                    <v-icon>mdi-pencil</v-icon>
                  </template>
                  <v-list-item-title>Edit</v-list-item-title>
                </v-list-item>
                <v-list-item v-if="item.api_key" @click="copyHospitalApiKey(item)">
                  <template v-slot:prepend>
                    <v-icon color="primary">mdi-content-copy</v-icon>
                  </template>
                  <v-list-item-title>Copy API Key</v-list-item-title>
                </v-list-item>
                <v-list-item @click="regenerateApiKey(item)">
                  <template v-slot:prepend>
                    <v-icon color="warning">mdi-key</v-icon>
                  </template>
                  <v-list-item-title>Regenerate API Key</v-list-item-title>
                </v-list-item>
                <v-list-item @click="deleteHospital(item)">
                  <template v-slot:prepend>
                    <v-icon color="error">mdi-delete</v-icon>
                  </template>
                  <v-list-item-title class="text-error">Delete</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-menu>
          </template>
        </v-data-table>
      </v-col>
    </v-row>

    <!-- Add/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="600">
      <v-card>
        <v-card-title>{{ editing ? 'Edit Hospital' : 'Add Hospital' }}</v-card-title>
        <v-card-text>
          <v-form>
            <v-text-field
              v-model="form.name"
              label="Name"
              :rules="[rules.required]"
              required
            ></v-text-field>
            <v-text-field
              v-model="form.code"
              label="Code"
              :rules="[rules.required]"
              required
            ></v-text-field>
            <v-select
              v-model="form.status"
              label="Status"
              :items="['active', 'suspended']"
              :rules="[rules.required]"
              required
            ></v-select>
            <v-alert v-if="newApiKey" type="success" class="mt-4">
              <strong>New API Key:</strong> {{ newApiKey }}
              <v-btn icon="mdi-content-copy" size="small" @click="copyApiKey"></v-btn>
            </v-alert>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="saveHospital">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const hospitals = ref([]);
const loading = ref(false);
const dialog = ref(false);
const editing = ref(false);
const newApiKey = ref(null);
const form = ref({
  id: null,
  name: '',
  code: '',
  status: 'active',
});
const rules = {
  required: (v) => !!v || 'Required',
};

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Name', key: 'name' },
  { title: 'Code', key: 'code' },
  { title: 'Status', key: 'status' },
  { title: 'Created', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
];

// Ensure form is reactive
const resetForm = () => {
  form.value = {
    id: null,
    name: '',
    code: '',
    status: 'active',
  };
};

const openDialog = (hospital = null) => {
  // Reset form first
  resetForm();
  
  if (hospital) {
    editing.value = true;
    // Extract only the fields needed for the form
    // Use Object.assign to ensure reactivity
    Object.assign(form.value, {
      id: hospital.id,
      name: hospital.name || '',
      code: hospital.code || '',
      status: hospital.status || 'active',
    });
  } else {
    editing.value = false;
  }
  newApiKey.value = null;
  dialog.value = true;
};

const editHospital = (hospital) => {
  openDialog(hospital);
};

// Vuetify v-data-table row click passes (event, { item, index, internalItem })
const onRowClickHospital = (event, payload) => {
  console.log('onRowClickHospital', payload.item);
  if (payload && payload.item) {
    editHospital(payload.item);
  }
};

const saveHospital = async () => {
  try {
    if (editing.value) {
      await axios.put(`/api/v1/admin/hospitals/${form.value.id}`, form.value);
    } else {
      const response = await axios.post('/api/v1/admin/hospitals', form.value);
      newApiKey.value = response.data.api_key;
    }
    dialog.value = false;
    loadHospitals();
  } catch (error) {
    console.error('Failed to save hospital:', error);
    alert(error.response?.data?.message || 'Failed to save hospital');
  }
};

const deleteHospital = async (hospital) => {
  if (!confirm(`Are you sure you want to delete ${hospital.name}?`)) return;
  try {
    await axios.delete(`/api/v1/admin/hospitals/${hospital.id}`);
    loadHospitals();
  } catch (error) {
    console.error('Failed to delete hospital:', error);
    alert(error.response?.data?.message || 'Failed to delete hospital');
  }
};

const regenerateApiKey = async (hospital) => {
  if (!confirm(`Are you sure you want to regenerate the API key for ${hospital.name}?`)) return;
  try {
    const response = await axios.post(`/api/v1/admin/hospitals/${hospital.id}/regenerate-api-key`);
    newApiKey.value = response.data.api_key;
    dialog.value = true;
    form.value = { ...hospital };
  } catch (error) {
    console.error('Failed to regenerate API key:', error);
    alert(error.response?.data?.message || 'Failed to regenerate API key');
  }
};

const copyApiKey = () => {
  navigator.clipboard.writeText(newApiKey.value);
  alert('API key copied to clipboard!');
};

const copyHospitalApiKey = async (hospital) => {
  if (!hospital.api_key) {
    alert('No API key available for this hospital');
    return;
  }
  
  try {
    await navigator.clipboard.writeText(hospital.api_key);
    alert('API key copied to clipboard!');
  } catch (error) {
    console.error('Failed to copy API key:', error);
    // Fallback for older browsers
    const textArea = document.createElement('textarea');
    textArea.value = hospital.api_key;
    textArea.style.position = 'fixed';
    textArea.style.opacity = '0';
    document.body.appendChild(textArea);
    textArea.select();
    try {
      document.execCommand('copy');
      alert('API key copied to clipboard!');
    } catch (err) {
      alert('Failed to copy API key. Please copy manually: ' + hospital.api_key);
    }
    document.body.removeChild(textArea);
  }
};

const loadHospitals = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/v1/admin/hospitals');
    // API Resource collection returns: { data: [...], success: true, links: {...}, meta: {...} }
    if (response.data.data && Array.isArray(response.data.data)) {
      hospitals.value = response.data.data;
    } else if (response.data.data?.data && Array.isArray(response.data.data.data)) {
      // Paginated response structure
      hospitals.value = response.data.data.data;
    } else {
      hospitals.value = [];
    }
  } catch (error) {
    console.error('Failed to load hospitals:', error);
    hospitals.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadHospitals();
});
</script>

