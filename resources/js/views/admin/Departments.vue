<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center mb-4">
          <h1 class="text-h4">Departments</h1>
          <v-btn color="primary" @click="openDialog()">
            <v-icon start>mdi-plus</v-icon>
            Add Department
          </v-btn>
        </div>
        <v-text-field
          v-model="search"
          label="Search"
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          class="mb-4"
          @input="loadDepartments"
        ></v-text-field>
        <v-data-table
          :headers="headers"
          :items="departments"
          :loading="loading"
          @click:row="onRowClickDepartment"
        >
          <template v-slot:item.is_active="{ item }">
            <v-chip :color="item.is_active ? 'success' : 'error'" small>
              {{ item.is_active ? 'Active' : 'Inactive' }}
            </v-chip>
          </template>
          <template v-slot:item.created_at="{ item }">
            {{ formatDate(item.created_at) }}
          </template>
          <template v-slot:item.actions="{ item }">
            <v-menu>
              <template v-slot:activator="{ props }">
                <v-btn icon="mdi-dots-vertical" size="small" v-bind="props" @click.stop></v-btn>
              </template>
              <v-list>
                <v-list-item @click.stop="editDepartment(item)">
                  <template v-slot:prepend>
                    <v-icon>mdi-pencil</v-icon>
                  </template>
                  <v-list-item-title>Edit</v-list-item-title>
                </v-list-item>
                <v-list-item @click.stop="manageIcd10Codes(item)">
                  <template v-slot:prepend>
                    <v-icon color="primary">mdi-tag-multiple</v-icon>
                  </template>
                  <v-list-item-title>Manage ICD-10 Codes</v-list-item-title>
                </v-list-item>
                <v-list-item @click.stop="deleteDepartment(item)" class="text-error">
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
        <v-card-title>{{ editing ? 'Edit Department' : 'Add Department' }}</v-card-title>
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
              hint="Short code (e.g., CARD, NEURO)"
            ></v-text-field>
            <v-textarea
              v-model="form.description"
              label="Description"
              rows="3"
              auto-grow
            ></v-textarea>
            <v-switch
              v-model="form.is_active"
              label="Active"
            ></v-switch>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="saveDepartment">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Manage ICD-10 Codes Dialog -->
    <v-dialog v-model="icd10Dialog" max-width="1200" scrollable>
      <v-card>
        <v-card-title class="d-flex justify-space-between align-center">
          <span>Manage ICD-10 Codes for {{ selectedDepartment?.name }}</span>
          <v-btn icon="mdi-close" variant="text" @click="icd10Dialog = false"></v-btn>
        </v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12" md="6">
              <div class="d-flex mb-2" style="gap: 0.5rem;">
                <v-text-field
                  v-model="icd10Search"
                  label="Search ICD-10 Codes"
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  density="compact"
                  class="flex-grow-1"
                  @input="loadIcd10Codes"
                ></v-text-field>
                <v-btn
                  variant="outlined"
                  @click="loadIcd10Codes(true)"
                  :loading="loadingIcd10"
                  style="min-width: 100px;"
                >
                  Load All
                </v-btn>
              </div>
              <v-data-table
                v-model="selectedIcd10Codes"
                :headers="icd10Headers"
                :items="availableIcd10Codes"
                :loading="loadingIcd10"
                :items-per-page="10"
                show-select
                return-object
                item-value="id"
                class="elevation-1"
                height="400"
              >
                <template v-slot:item.code="{ item }">
                  <strong>{{ item.code }}</strong>
                </template>
                <template v-slot:item.description="{ item }">
                  <div class="text-caption">{{ item.description }}</div>
                </template>
              </v-data-table>
            </v-col>
            <v-col cols="12" md="6">
              <div class="text-h6 mb-2">Selected Codes ({{ selectedMappings.length }})</div>
              <v-card variant="outlined" class="pa-2" style="max-height: 400px; overflow-y: auto;">
                <div v-if="selectedMappings.length === 0" class="text-center text-grey pa-4">
                  No codes selected. Select codes from the left table.
                </div>
                <v-card
                  v-for="(mapping, index) in selectedMappings"
                  :key="mapping.id"
                  variant="outlined"
                  class="mb-2 pa-2"
                >
                  <div class="d-flex justify-space-between align-start">
                    <div class="flex-grow-1">
                      <div class="font-weight-bold">{{ mapping.code }}</div>
                      <div class="text-caption text-grey">{{ mapping.description }}</div>
                    </div>
                    <v-btn
                      icon="mdi-close"
                      size="small"
                      variant="text"
                      @click="removeMapping(mapping.id)"
                    ></v-btn>
                  </div>
                  <v-row class="mt-2">
                    <v-col cols="6">
                      <v-text-field
                        v-model.number="mapping.priority"
                        label="Priority"
                        type="number"
                        min="1"
                        max="10"
                        density="compact"
                        variant="outlined"
                        hint="1 = highest priority"
                        persistent-hint
                      ></v-text-field>
                    </v-col>
                    <v-col cols="6" class="d-flex align-center">
                      <v-switch
                        v-model="mapping.is_primary"
                        label="Primary"
                        density="compact"
                        color="primary"
                      ></v-switch>
                    </v-col>
                    <v-col cols="12">
                      <v-textarea
                        v-model="mapping.notes"
                        label="Notes (optional)"
                        rows="2"
                        density="compact"
                        variant="outlined"
                        auto-grow
                      ></v-textarea>
                    </v-col>
                  </v-row>
                </v-card>
              </v-card>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="icd10Dialog = false">Cancel</v-btn>
          <v-btn
            color="primary"
            @click="saveIcd10Mappings"
            :loading="savingIcd10"
            :disabled="selectedMappings.length === 0"
          >
            Save Mappings
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

const departments = ref([]);
const loading = ref(false);
const dialog = ref(false);
const editing = ref(false);
const search = ref('');
const form = ref({
  id: null,
  name: '',
  code: '',
  description: '',
  is_active: true,
});
const rules = {
  required: (v) => !!v || 'Required',
};

// ICD-10 Code Management
const icd10Dialog = ref(false);
const selectedDepartment = ref(null);
const availableIcd10Codes = ref([]);
const selectedIcd10Codes = ref([]);
const selectedMappings = ref([]);
const loadingIcd10 = ref(false);
const savingIcd10 = ref(false);
const icd10Search = ref('');
const icd10Headers = [
  { title: 'Code', key: 'code' },
  { title: 'Description', key: 'description' },
  { title: 'Category', key: 'category' },
];

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Name', key: 'name' },
  { title: 'Code', key: 'code' },
  { title: 'Description', key: 'description' },
  { title: 'Status', key: 'is_active' },
  { title: 'Created', key: 'created_at' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const formatDate = (dateString) => {
  return dateString || '-';
};

const resetForm = () => {
  form.value = {
    id: null,
    name: '',
    code: '',
    description: '',
    is_active: true,
  };
};

const openDialog = (department = null) => {
  resetForm();
  
  if (department) {
    editing.value = true;
    Object.assign(form.value, {
      id: department.id,
      name: department.name || '',
      code: department.code || '',
      description: department.description || '',
      is_active: department.is_active !== undefined ? department.is_active : true,
    });
  } else {
    editing.value = false;
  }
  dialog.value = true;
};

const editDepartment = (department) => {
  openDialog(department);
};

const onRowClickDepartment = (event, payload) => {
  if (payload && payload.item) {
    editDepartment(payload.item);
  }
};

const saveDepartment = async () => {
  try {
    if (editing.value) {
      await axios.put(`/api/v1/admin/departments/${form.value.id}`, form.value);
    } else {
      await axios.post('/api/v1/admin/departments', form.value);
    }
    dialog.value = false;
    loadDepartments();
  } catch (error) {
    console.error('Failed to save department:', error);
    alert(error.response?.data?.message || 'Failed to save department');
  }
};

const deleteDepartment = async (department) => {
  if (!confirm(`Are you sure you want to delete department "${department.name}"?`)) return;
  try {
    await axios.delete(`/api/v1/admin/departments/${department.id}`);
    loadDepartments();
  } catch (error) {
    console.error('Failed to delete department:', error);
    alert(error.response?.data?.message || 'Failed to delete department');
  }
};

const loadDepartments = async () => {
  loading.value = true;
  try {
    const params = {};
    if (search.value) {
      params.search = search.value;
    }
    const response = await axios.get('/api/v1/admin/departments', { params });
    if (response.data.data && Array.isArray(response.data.data)) {
      departments.value = response.data.data;
    } else if (response.data.data?.data && Array.isArray(response.data.data.data)) {
      departments.value = response.data.data.data;
    } else {
      departments.value = [];
    }
  } catch (error) {
    console.error('Failed to load departments:', error);
    departments.value = [];
  } finally {
    loading.value = false;
  }
};

const manageIcd10Codes = async (department) => {
  selectedDepartment.value = department;
  selectedIcd10Codes.value = [];
  selectedMappings.value = [];
  icd10Search.value = '';
  icd10Dialog.value = true;
  
  // Load existing mappings
  await loadDepartmentIcd10Codes(department.id);
  // Load available ICD-10 codes
  await loadIcd10Codes();
};

const loadDepartmentIcd10Codes = async (departmentId) => {
  try {
    const response = await axios.get(`/api/v1/admin/departments/${departmentId}/icd10-codes`);
    if (response.data.success && response.data.data) {
      selectedMappings.value = response.data.data.map(code => ({
        id: code.id,
        code: code.code,
        description: code.description,
        category: code.category,
        priority: code.priority || 1,
        is_primary: code.is_primary || false,
        notes: code.notes || '',
      }));
      // Also select them in the table
      selectedIcd10Codes.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load department ICD-10 codes:', error);
  }
};

const loadIcd10Codes = async (loadAll = false) => {
  loadingIcd10.value = true;
  try {
    const params = { per_page: loadAll ? 1000 : 15 };
    if (icd10Search.value) {
      params.search = icd10Search.value;
    }
    const response = await axios.get('/api/v1/admin/icd10-codes', { params });
    let codes = [];
    if (response.data.data && Array.isArray(response.data.data)) {
      codes = response.data.data;
    } else if (response.data.data?.data && Array.isArray(response.data.data.data)) {
      codes = response.data.data.data;
    }
    
    // Filter out already selected codes
    const selectedIds = selectedMappings.value.map(m => m.id);
    availableIcd10Codes.value = codes.filter(code => !selectedIds.includes(code.id));
  } catch (error) {
    console.error('Failed to load ICD-10 codes:', error);
    availableIcd10Codes.value = [];
  } finally {
    loadingIcd10.value = false;
  }
};

// Watch for selection changes in the data table
watch(selectedIcd10Codes, (newSelection, oldSelection) => {
  // Add newly selected codes to mappings
  newSelection.forEach(code => {
    const exists = selectedMappings.value.find(m => m.id === code.id);
    if (!exists) {
      selectedMappings.value.push({
        id: code.id,
        code: code.code,
        description: code.description,
        category: code.category,
        priority: 1,
        is_primary: false,
        notes: '',
      });
    }
  });
  
  // Remove unselected codes (compare with old selection)
  const newIds = newSelection.map(c => c.id);
  selectedMappings.value = selectedMappings.value.filter(mapping => {
    return newIds.includes(mapping.id);
  });
}, { deep: true });

const removeMapping = (codeId) => {
  // Remove from mappings
  selectedMappings.value = selectedMappings.value.filter(m => m.id !== codeId);
  // Remove from selected codes
  selectedIcd10Codes.value = selectedIcd10Codes.value.filter(c => c.id !== codeId);
  // Find the code in available codes or reload to show it
  const removedCode = availableIcd10Codes.value.find(c => c.id === codeId);
  if (!removedCode) {
    // If not found, reload to get it back
    loadIcd10Codes();
  }
};

const saveIcd10Mappings = async () => {
  if (!selectedDepartment.value) return;
  
  savingIcd10.value = true;
  try {
    const mappings = selectedMappings.value.map(m => ({
      id: m.id,
      priority: m.priority || 1,
      is_primary: m.is_primary || false,
      notes: m.notes || null,
    }));
    
    await axios.post(`/api/v1/admin/departments/${selectedDepartment.value.id}/icd10-codes`, {
      icd10_codes: mappings,
    });
    
    alert('ICD-10 codes updated successfully!');
    icd10Dialog.value = false;
  } catch (error) {
    console.error('Failed to save ICD-10 mappings:', error);
    alert(error.response?.data?.message || 'Failed to save ICD-10 code mappings');
  } finally {
    savingIcd10.value = false;
  }
};

onMounted(() => {
  loadDepartments();
});
</script>

