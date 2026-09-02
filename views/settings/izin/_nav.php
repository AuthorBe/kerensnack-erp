<!-- Segmented Navigation Control (Standardized 1:1 Rekap-Mukholif) -->
<nav class="nav-segmented-control" aria-label="Navigasi Pengaturan Izin">
    <button type="button" @click="setTab('user')" class="nav-segment-link" :class="activeTab === 'user' ? 'active' : ''">
        <i data-lucide="user-check" style="width: 15px; height: 15px;"></i>
        <span>Izin User</span>
    </button>
    <button type="button" @click="setTab('role')" class="nav-segment-link" :class="activeTab === 'role' ? 'active' : ''">
        <i data-lucide="shield" style="width: 15px; height: 15px;"></i>
        <span>Default Role</span>
    </button>
    <button type="button" @click="setTab('manage_roles')" class="nav-segment-link" :class="activeTab === 'manage_roles' ? 'active' : ''">
        <i data-lucide="tags" style="width: 15px; height: 15px;"></i>
        <span>Kelola Role</span>
    </button>
    <button type="button" @click="setTab('matrix')" class="nav-segment-link" :class="activeTab === 'matrix' ? 'active' : ''">
        <i data-lucide="list-checks" style="width: 15px; height: 15px;"></i>
        <span>Daftar Izin</span>
    </button>
</nav>
