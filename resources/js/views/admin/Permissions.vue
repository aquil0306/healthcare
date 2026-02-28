<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <div class="d-flex justify-space-between align-center mb-4">
          <h1 class="text-h4">Permissions</h1>
          <v-btn color="primary" @click="openDialog()">
            <v-icon start>mdi-plus</v-icon>
            Add Permission
          </v-btn>
        </div>
        <v-text-field
          v-model="search"
          label="Search"
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          class="mb-4"
          @input="loadPermissions"
        ></v-text-field>
        <v-data-table
          :headers="headers"
          :items="permissions"
          :loading="loading"
          @click:row="(event, row) => editPermission(row.item)"
        >
          <template v-slot:item.roles_count="{ item }">
            {{ item.roles_count }} role(s)
          </template>
          <template v-slot:item.actions="{ item }">
            <v-menu>
              <template v-slot:activator="{ props }">
                <v-btn icon="mdi-dots-vertical" size="small" v-bind="props" @click.stop></v-btn>
              </template>
              <v-list>
                <v-list-item @click="editPermission(item)">
                  <template v-slot:prepend>
                    <v-icon>mdi-pencil</v-icon>
                  </template>
                  <v-list-item-title>Edit</v-list-item-title>
                </v-list-item>
                <v-list-item @click="deletePermission(item)">
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
        <v-card-title>{{ editing ? 'Edit Permission' : 'Add Permission' }}</v-card-title>
        <v-card-text>
          <v-form ref="formRef">
            <v-text-field
              v-model="form.name"
              label="Permission Name"
              :rules="[rules.required]"
              required
              hint="Format: resource.action (e.g., referrals.view, hospitals.create)"
              persistent-hint
              :disabled="loading"
            ></v-text-field>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="savePermission">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';

const permissions = ref([]);
const loading = ref(false);
const dialog = ref(false);
const editing = ref(false);
const search = ref('');
const formRef = ref(null);
const form = ref({
  id: null,
  name: '',
});
const rules = {
  required: (v) => !!v || 'Required',
};

const headers = [
  { title: 'ID', key: 'id' },
  { title: 'Name', key: 'name' },
  { title: 'Assigned to Roles', key: 'roles_count' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const openDialog = async (permission = null) => {
  if (permission) {
    editing.value = true;
    // Ensure we copy the values to maintain reactivity
    form.value.id = permission.id;
    form.value.name = permission.name || '';
  } else {
    editing.value = false;
    form.value.id = null;
    form.value.name = '';
  }
  // Wait for next tick to ensure form is updated before opening dialog
  await nextTick();
  dialog.value = true;
};

const editPermission = (permission) => {
  // Handle both row click and menu click
  if (permission && permission.id) {
    openDialog(permission);
  }
};

const savePermission = async () => {
  // Validate form
  if (formRef.value) {
    const { valid } = await formRef.value.validate();
    if (!valid) return;
  }

  if (!form.value.name) {
    alert('Permission name is required');
    return;
  }

  try {
    loading.value = true;
    if (editing.value && form.value.id) {
      await axios.put(`/api/v1/admin/permissions/${form.value.id}`, {
        name: form.value.name,
      });
    } else {
      await axios.post('/api/v1/admin/permissions', {
        name: form.value.name,
      });
    }
    dialog.value = false;
    // Reset form
    form.value = { id: null, name: '' };
    editing.value = false;
    await loadPermissions();
  } catch (error) {
    console.error('Failed to save permission:', error);
    alert(error.response?.data?.message || 'Failed to save permission');
  } finally {
    loading.value = false;
  }
};

const deletePermission = async (permission) => {
  if (!confirm(`Are you sure you want to delete permission "${permission.name}"?`)) return;
  try {
    await axios.delete(`/api/v1/admin/permissions/${permission.id}`);
    loadPermissions();
  } catch (error) {
    console.error('Failed to delete permission:', error);
    alert(error.response?.data?.message || 'Failed to delete permission');
  }
};

const loadPermissions = async () => {
  loading.value = true;
  try {
    const params = search.value ? { search: search.value } : {};
    const response = await axios.get('/api/v1/admin/permissions', { params });
    if (response.data.success && response.data.data) {
      permissions.value = response.data.data.data || response.data.data;
    } else {
      console.error('Unexpected response structure:', response.data);
      permissions.value = [];
    }
  } catch (error) {
    console.error('Failed to load permissions:', error);
    if (error.response) {
      console.error('Response error:', error.response.data);
      alert('Failed to load permissions: ' + (error.response.data?.message || error.message));
    }
    permissions.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadPermissions();
});
</script>

