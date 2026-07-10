<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            
            <div class="module-header detail-header-flex">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight leading-tight">Gestión de Gastos</h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium uppercase tracking-widest mt-1">Visualiza tus gastos asignados y gestiona los comprobantes de forma directa.</p>
                </div>
                <button 
                    @click="router.push({ name: 'GastosCreate' })" 
                    class="btn-primary flex items-center justify-center gap-2 px-6 py-3 rounded-2xl text-sm font-black shadow-lg shadow-red-100 transition-all"
                >
                    <i class="fas fa-plus-circle"></i> Registrar Nuevo Gasto
                </button>
            </div>

            <div class="filter-section form-section bck mt-6 shadow-sm border border-slate-200">
                <div class="section-title">
                    <i class="fas fa-filter text-slate-400"></i> Filtros y Búsqueda
                </div>
                <div class="filter-grid mt-4">
                    <div class="form-group col-span-2">
                        <label for="search">Paquete de Gastos:</label>
                        <div class="relative">
                            <i class="fas fa-search search-icon text-slate-400"></i>
                            <input 
                                type="text" 
                                id="search" 
                                v-model="filters.search" 
                                class="form-input pl-10" 
                                placeholder="Ej: Hidalgo, CDMX..."
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="registroStatus">Estado:</label>
                        <select v-model="filters.status" id="registroStatus" class="form-input font-bold">
                            <option value="all">Todos los estados</option>
                            <option value="BORRADOR">Borradores</option>
                            <option value="FINALIZADO">Finalizados</option>
                        </select>
                    </div>
                    
                    <div class="form-group flex items-end gap-2">
                        <button @click="fetchGastos" class="btn-primary flex-1 py-3 text-xs">
                            <i class="fas fa-sync-alt"></i> Buscar
                        </button>
                        <button @click="resetFilters" class="btn-secondary px-4 text-slate-400" title="Limpiar Filtros">
                            <i class="fas fa-eraser"></i>Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <br>
            <!-- TABLA DE GASTOS -->
            <div v-if="loading" class="loading-state mt-8 text-center py-20">
                <i class="fas fa-circle-notch fa-spin text-4xl mb-4 text-red-600"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]">Sincronizando información...</p>
            </div>

            <div v-else-if="error" class="error-message text-center py-10 bg-red-50 rounded-[2.5rem] border-2 border-red-100 mx-2">
                <i class="fas fa-exclamation-circle text-red-600 text-2xl mb-2"></i>
                <p class="text-red-800 font-black uppercase text-xs">{{ error }}</p>
            </div>

            <div v-else-if="filteredGastos.length === 0" class="mt-8 text-center py-20 bg-white rounded-[3rem] border-2 border-dashed border-slate-100 shadow-sm">
                <i class="fas fa-folder-open mb-3 text-3xl text-slate-200"></i>
                <p class="text-slate-500 font-bold uppercase text-[10px] tracking-widest">No se encontraron gastos con estos criterios.</p>
            </div>

           <div v-else class="table-responsive table-shadow-lg mt-8 border rounded-[2rem] overflow-hidden shadow-sm bg-white animate-fade-in">
    <table class="min-width-full divide-y divide-slate-100 responsive-table">
        <thead class="bg-slate-900 hidden md:table-header-group">
            <tr>
                <th class="table-header w-32 text-white">Fecha</th>
                <th class="table-header text-white">Paquete de gastos</th>
                <th class="table-header text-center w-36 text-white">Estado</th>
                <th class="table-header text-right w-36 text-white">Monto</th>
                <th class="px-6 py-3 w-28"></th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-slate-100 block md:table-row-group">
            <tr v-for="gasto in filteredGastos" :key="gasto.id" 
                class="hover:bg-slate-50/50 transition-colors block md:table-row relative p-5 md:p-0 border-b md:border-none"
                :class="{'bg-amber-50/10': gasto.status === 'BORRADOR'}">
                
                <td class="table-cell table-cell-bold text-slate-700 block md:table-cell" data-label="FECHA">
                    {{ formatDate(gasto.fecha) }}
                    <br><label v-if="currentUserRole !== 'promotor'" style="color:gray; font-size:9px">{{ gasto.user.full_name }}</label>
                </td>

                <td class="table-cell block md:table-cell" data-label="PAQUETE DE GASTOS">
                    <div class="text-sm font-black uppercase leading-tight text-truncate max-w-concepto" :title="gasto.concepto">
                          <i class="fas text-slate-700 sl7 fa-map-marker-alt mr-1 opacity-50"></i> {{ gasto.estado_nombre }}
                    </div>
                </td>

                <td class="table-cell text-left md:text-center block md:table-cell" data-label="ESTADO">
                    <span class="status-badge" 
                          :class="gasto.status === 'BORRADOR' ? 'badge-draft' : 'badge-final'">
                        <i :class="gasto.status === 'BORRADOR' ? 'fas fa-edit' : 'fas fa-lock'" class="mr-1.5 opacity-60"></i>
                        {{ gasto.status }}
                    </span>
                </td>

                <td class="table-cell table-cell-bold text-left md:text-right text-red-700 text-lg block md:table-cell" data-label="MONTO">
                    {{ formatCurrency(gasto.monto) }}
                </td>

                <td class="table-cell text-right block md:table-cell">
                    <button @click="viewDetails(gasto)" class="text-red-link flex items-center justify-end gap-1 w-full">
                        <i class="fas fa-eye"></i> Detalles
                    </button>
                </td>
            </tr>
        </tbody>
    </table>
    <div v-if="lastPage > 1" class="pagination-container flex items-center justify-between mt-6">
        <div class="hidden sm:flex sm:flex-row sm:items-center sm:justify-between w-full gap-4">
            <div class="flex items-center">
                <nav class="flex flex-row items-center gap-1.5" aria-label="Pagination">
                    <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1" class="btn-page-nav flex items-center justify-center">
                        <ChevronLeft />
                    </button>
                    
                    <button v-for="page in lastPage" :key="page"
                        v-show="page === 1 || page === lastPage || Math.abs(page - currentPage) <= 1"
                        @click="changePage(page)"
                        class="btn-page-number"
                        :class="page === currentPage ? 'btn-page-number-active shadow-md' : 'btn-page-number-inactive'"
                    >
                        {{ page }}
                    </button>

                    <button @click="changePage(currentPage + 1)" :disabled="currentPage === lastPage" class="btn-page-nav flex items-center justify-center">
                        <ChevronRight />
                    </button>
                </nav>
            </div>
        </div>
        <div class="flex flex-1 justify-between sm:hidden">
            <div class="flex items-center">
                <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest m-0 whitespace-nowrap">
                    Página <span class="text-red-700 font-black text-xs px-2 py-0.5 bg-red-50 rounded-lg mx-1"><b>{{ currentPage }}</b></span> 
                    de <span class="text-slate-700 font-black text-xs ml-1"><b>{{ lastPage }}</b></span>
                </p>
            </div>
        </div>
    </div>
</div>
            
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const router = useRouter();
const gastos = ref([]);
const loading = ref(false);
const error = ref(null);

const currentPage = ref(1);
const lastPage = ref(1);
const totalPedidos = ref(0);

const currentUserRole = ref('');

const filters = reactive({
    search: '',
    fecha_desde: '',
    fecha_hasta: '',
    tiene_comprobante: 'all', 
    status: 'all',
    modificado: 'all' 
});

/**
 * Lógica para determinar si un registro fue editado.
 * Se considera editado si updated_at es posterior a created_at por más de 2 segundos.
 */
const isEdited = (g) => {
    if (!g.created_at || !g.updated_at) return false;
    const created = new Date(g.created_at).getTime();
    const updated = new Date(g.updated_at).getTime();
    return updated > (created + 2000);
};

const filteredGastos = computed(() => {
    let result = [...gastos.value];

    // 1. Filtrar por búsqueda de texto
    if (filters.search.trim() !== '') {
        const searchTerm = filters.search.toLowerCase();
        result = result.filter(g => 
            g.concepto.toLowerCase().includes(searchTerm) || 
            g.estado_nombre?.toLowerCase().includes(searchTerm)
        );
    }

    // 2. Filtrar por estado del registro
    if (filters.status !== 'all') {
        result = result.filter(g => g.status === filters.status);
    }

    // 3. Filtrar por presencia de comprobantes
    if (filters.tiene_comprobante === 'missing') {
        result = result.filter(g => !g.comprobantes || g.comprobantes.length === 0);
    } else if (filters.tiene_comprobante === 'uploaded') {
        result = result.filter(g => g.comprobantes && g.comprobantes.length > 0);
    }

    // 4. Filtrar por estado de edición (Logica nueva)
    if (filters.modificado === 'edited') {
        result = result.filter(g => isEdited(g));
    } else if (filters.modificado === 'original') {
        result = result.filter(g => !isEdited(g));
    }

    // 5. Ordenar: Nuevos arriba
    result.sort((a, b) => b.id - a.id);

    return result;
});

const fetchGastos = async (page = 1) => {
    loading.value = true;
    error.value = null;
    currentPage.value = page; // Guardamos la página que se está consultando
    
    try {
        // Enviamos la página actual en los parámetros de la petición Axios
        const response = await axios.get('/gastos', {
            params: { page: page }
        }); 
        
        // Laravel paginate entrega la estructura completa en response.data
        const paginatedData = response.data;
        
        if (paginatedData && typeof paginatedData === 'object' && 'data' in paginatedData) {
            gastos.value = Array.isArray(paginatedData.data) ? paginatedData.data : [];
            
            // Seteamos el estado de la paginación usando la respuesta del backend
            currentPage.value = paginatedData.current_page;
            lastPage.value = paginatedData.last_page;
        } else {
            // Respaldo en caso de que el controlador devuelva la lista directa sin paginar
            const data = response.data.data || response.data;
            gastos.value = Array.isArray(data) ? data : []; 
            lastPage.value = 1;
        }
    } catch (err) {
        error.value = 'No se pudo sincronizar el historial de gastos.';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    if (page >= 1 && page <= lastPage.value) {
        fetchGastos(page);
    }
};

const resetFilters = () => {
    Object.assign(filters, {
        search: '',
        fecha_desde: '',
        fecha_hasta: '',
        tiene_comprobante: 'all',
        status: 'all',
        modificado: 'all'
    });
    fetchGastos();
};

const viewDetails = (gasto) => {
    router.push({ name: 'GastosDetail', params: { id: gasto.id } });
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatCurrency = (value) => {
    return Number(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
};

onMounted(async () => {
    await fetchGastos();
    
    try {
        const response = await axios.get('/user');
        if (response.data && response.data.role) {
            currentUserRole.value = response.data.role;
        }
    } catch (err) {
        console.warn("No se pudo recuperar el rol del usuario desde el servidor directamente.");
    }
});
</script>

<style scoped>
.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    align-items: flex-end;
}
.bck{
 background-color: #fdfdfd; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px;
}

@media (min-width: 1024px) {
    .col-span-2 {
        grid-column: span 2;
    }
}

.relative { position: relative; }
.pl-10 { padding-left: 2.5rem; }

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
    z-index: 10;
}

.text-red-link {
    background: none;
    border: none;
    cursor: pointer;
    color: #b91c1c;
    text-decoration: none;
    transition: color 0.2s;
    font-weight: 900;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.text-red-link:hover {
    color: #7f1d1d;
    text-decoration: underline;
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
    background: white;
}

table {
    table-layout: fixed;
    width: 100%;
}

.table-header {
    padding: 18px 20px;
    font-size: 0.7rem;
    font-weight: 900;
    color: #64748b;
    text-transform: uppercase;
    text-align: left;
    letter-spacing: 0.1em;
}

.table-cell {
    padding: 20px;
    font-size: 0.85rem;
    vertical-align: middle;
}

.table-cell-bold {
    font-weight: 800;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.max-w-concepto {
    max-width: 250px;
}

.status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

/* Badges para Archivos */
.badge-completed {
    background-color: #f0f9ff;
    color: #0369a1;
}

.badge-pending {
    background-color: #fef2f2;
    color: #b91c1c;
}

/* Badges para Estado Registro */
.badge-draft {
    background-color: #fffbeb;
    color: #b45309;
}

.badge-final {
    background-color: #95c48e;
    color: #ffffff;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Badges para Auditoría de Modificaciones */
.badge-edited {
    background-color: #f5f3ff;
    color: #7c3aed;
    border: 1px solid #ddd6fe;
}

.badge-original {
    background-color: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.table-shadow-lg {
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
}

.section-title { 
    font-weight: 900; 
    color: #1e293b; 
    display: flex; 
    align-items: center; 
    gap: 10px; 
    text-transform: uppercase; 
    font-size: 0.8rem; 
    letter-spacing: 1px; 
}

.text-right { text-align: right; }
.text-center { text-align: center; }

.btn-primary { 
    background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); 
    color: white; 
    border-radius: 20px; 
    font-weight: 900; 
    cursor: pointer; 
    border: none; 
    box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); 
    transition: all 0.2s; 
    text-transform: uppercase; 
    font-size: 0.8rem; 
    letter-spacing: 0.05em; 
}
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.btn-secondary {
    padding: 8px 15px;
    background: white;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    cursor: pointer;
}
.btn-secondary:hover { background-color: #f8fafc; border-color: #94a3b8; }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-2 block tracking-widest; }

.shadow-premium { box-shadow: 0 15px 35px -10px rgba(0,0,0,0.05); }

.sl7{
    font-weight: bold;
}

/* ── AJUSTE DE CLASES CSS PARA ALINEACIÓN HORIZONTAL ESTRICTA ── */
.pagination-container {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    background-color: #fdfdfd;
    border: 1px solid #e2e8f0;
    padding: 14px 20px;
    border-radius: 12px;
}

.btn-page-nav {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    border: 2px solid #f1f5f9;
    font-weight: 800;
    color: #94a3b8;
    background: #fafbfc;
    transition: all 0.2s;
}

.btn-page-nav:hover:not(:disabled) {
    border-color: #cbd5e1;
    color: #64748b;
    background: white;
}

.btn-page-number {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 800;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-page-number-active {
    background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%);
    border: 2px solid #f1f5f9;
    color: white;
    font-weight: 900;
}

.btn-page-number-inactive {
    border: 2px solid #f1f5f9;
    color: #334155;
    background: #fafbfc;
}

.btn-page-number-inactive:hover {
    border-color: #e4989c;
    background: white;
}

/* Botón móvil adaptado a tu estilo de "Limpiar filtro" */
.btn-page-mobile {
    padding: 10px 15px;
    background: white;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    color: #64748b;
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
}
.btn-page-mobile:hover:not(:disabled) {
    color: #b91c1c;
    border-color: #b91c1c;
}
</style>