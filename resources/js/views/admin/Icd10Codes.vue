<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center mb-4">
          <h1 class="text-h4">ICD-10 Codes</h1>
          <v-btn color="primary" @click="openDialog()">
            <v-icon start>mdi-plus</v-icon>
            Add ICD-10 Code
          </v-btn>
        </div>
        <v-text-field
          v-model="search"
          label="Search"
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          class="mb-4"
          @input="loadIcd10Codes"
        ></v-text-field>
        <v-data-table
          :headers="headers"
          :items="icd10Codes"
          :loading="loading"
          @click:row="onRowClickIcd10Code"
        >
          <template v-slot:item.is_active="{ item }">
            <v-chip :color="item.is_active ? 'success' : 'error'" small>
              {{ item.is_active ? 'Active' : 'Inactive' }}
            </v-chip>
          </template>
          <template v-slot:item.category="{ item }">
            <div v-if="item.category">
              <div class="font-weight-bold">{{ item.category }}</div>
              <div class="text-caption text-grey">{{ item.category_description }}</div>
            </div>
            <span v-else class="text-grey">-</span>
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
                <v-list-item @click.stop="editIcd10Code(item)">
                  <template v-slot:prepend>
                    <v-icon>mdi-pencil</v-icon>
                  </template>
                  <v-list-item-title>Edit</v-list-item-title>
                </v-list-item>
                <v-list-item @click.stop="deleteIcd10Code(item)" class="text-error">
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
    <v-dialog v-model="dialog" max-width="700">
      <v-card>
        <v-card-title>{{ editing ? 'Edit ICD-10 Code' : 'Add ICD-10 Code' }}</v-card-title>
        <v-card-text>
          <v-form>
            <v-text-field
              v-model="form.code"
              label="Code"
              :rules="[rules.required]"
              required
              hint="e.g., I10, A00-B99"
            ></v-text-field>
            <v-textarea
              v-model="form.description"
              label="Description"
              :rules="[rules.required]"
              required
              rows="3"
              auto-grow
            ></v-textarea>
            <v-text-field
              v-model="form.category"
              label="Category Range"
              hint="e.g., A00-B99, I00-I99"
            ></v-text-field>
            <v-textarea
              v-model="form.category_description"
              label="Category Description"
              rows="2"
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
          <v-btn color="primary" @click="saveIcd10Code">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const icd10Codes = ref([]);
const loading = ref(false);
const dialog = ref(false);
const editing = ref(false);
const search = ref('');
const form = ref({
  id: null,
  code: '',
  description: '',
  category: '',
  category_description: '',
  is_active: true,
});
const rules = {
  required: (v) => !!v || 'Required',
};

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Code', key: 'code' },
  { title: 'Description', key: 'description' },
  { title: 'Category', key: 'category' },
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
    code: '',
    description: '',
    category: '',
    category_description: '',
    is_active: true,
  };
};

const openDialog = (icd10Code = null) => {
  resetForm();
  
  if (icd10Code) {
    editing.value = true;
    Object.assign(form.value, {
      id: icd10Code.id,
      code: icd10Code.code || '',
      description: icd10Code.description || '',
      category: icd10Code.category || '',
      category_description: icd10Code.category_description || '',
      is_active: icd10Code.is_active !== undefined ? icd10Code.is_active : true,
    });
  } else {
    editing.value = false;
  }
  dialog.value = true;
};

const editIcd10Code = (icd10Code) => {
  openDialog(icd10Code);
};

const onRowClickIcd10Code = (event, payload) => {
  if (payload && payload.item) {
    editIcd10Code(payload.item);
  }
};

const saveIcd10Code = async () => {
  try {
    if (editing.value) {
      await axios.put(`/api/v1/admin/icd10-codes/${form.value.id}`, form.value);
    } else {
      await axios.post('/api/v1/admin/icd10-codes', form.value);
    }
    dialog.value = false;
    loadIcd10Codes();
  } catch (error) {
    console.error('Failed to save ICD-10 code:', error);
    alert(error.response?.data?.message || 'Failed to save ICD-10 code');
  }
};

const deleteIcd10Code = async (icd10Code) => {
  if (!confirm(`Are you sure you want to delete ICD-10 code "${icd10Code.code}"?`)) return;
  try {
    await axios.delete(`/api/v1/admin/icd10-codes/${icd10Code.id}`);
    loadIcd10Codes();
  } catch (error) {
    console.error('Failed to delete ICD-10 code:', error);
    alert(error.response?.data?.message || 'Failed to delete ICD-10 code');
  }
};

const loadIcd10Codes = async () => {
  loading.value = true;
  try {
    const params = {};
    if (search.value) {
      params.search = search.value;
    }
    const response = await axios.get('/api/v1/admin/icd10-codes', { params });
    if (response.data.data && Array.isArray(response.data.data)) {
      icd10Codes.value = response.data.data;
    } else if (response.data.data?.data && Array.isArray(response.data.data.data)) {
      icd10Codes.value = response.data.data.data;
    } else {
      icd10Codes.value = [];
    }
  } catch (error) {
    console.error('Failed to load ICD-10 codes:', error);
    icd10Codes.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadIcd10Codes();
});
</script>

