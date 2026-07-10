<template>
    <div class="content-wrapper">
        <div class="module-page">
            
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Resumen de Pedidos</h1>
                    <p>Pedidos generados por tu cuenta, ordenados por fecha reciente.</p>
                </div>
                <router-link to="/GenerarPedido" class="btn-primary flex-row-centered gap-2 px-6 shadow-lg"> 
                    <i class="fas fa-plus-circle"></i> Nuevo Pedido
                </router-link>
            </div>

            <div class="filter-section bkk form-section shadow-premium border border-slate-100 rounded-[2rem] bg-white p-6 mb-8 animate-fade-in">
                <div class="section-title mb-4 font-black text-slate-700 uppercase tracking-widest text-xs flex items-center gap-2">
                    <i class="fas fa-filter text-red-600"></i> Filtros y Búsqueda
                </div>
                
                <div class="filter-grid">
                    <div class="form-group md:col-span-2">
                        <label class="label-header" for="search">Buscar Pedido o Cliente:</label>
                        <div class="relative">
                            <i class="fas fa-search search-icon"></i>
                            <input 
                                type="text" 
                                id="search"
                                v-model="filters.search" 
                                class="form-input pl-10 w-full" 
                                placeholder="Folio, nombre del cliente..."
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="label-header" for="status">Estado:</label>
                        <select v-model="filters.status" id="status" class="form-input w-full">
                            <option value="">Todos los estados</option>
                            <option value="PENDIENTE">PENDIENTE</option>
                            <option value="EN PROCESO">EN PROCESO</option>
                            <option value="ENTREGADO">ENTREGADO</option>
                            <option value="CANCELADO">CANCELADO</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="label-header" for="priority">Prioridad:</label>
                        <select v-model="filters.priority" id="priority" class="form-input w-full">
                            <option value="">Cualquier prioridad</option>
                            <option value="alta">ALTA</option>
                            <option value="media">MEDIA</option>
                            <option value="baja">BAJA</option>
                        </select>
                    </div>
                </div>
                <div class="filter-grid">
                    <div class="form-group flex items-end gap-2 md:col-span-4 lg:col-span-1">
                        <button @click="fetchPedidos" class="btn-primary flex-1 h-[42px] flex items-center justify-center gap-2">
                            <i class="fas fa-sync-alt"></i> Buscar
                        </button>
                    </div>
                    <div class="form-group flex items-end gap-2 md:col-span-4 lg:col-span-1">
                        <button :disabled="!hasFilters" @click="resetFilters" class="btn-secondary h-[42px] px-4 rounded-xl border-2 border-slate-100 text-slate-400 hover:text-red-600 transition-colors" title="Limpiar Filtros">
                            <i class="fas fa-eraser"></i>Limpiar filtro
                        </button>
                    </div>
                    <div></div>
                    <div class="form-group flex items-end gap-2 md:col-span-4 lg:col-span-1">
                        <button v-if="currentUserRole === 'admin'" @click="descargarPedidos"
                            :disabled="!canDownload"
                            type="button"
                            class="btn-primary flex-1 h-[42px] flex items-center justify-center gap-2">
                            Descargar
                        </button>
                    </div>
                </div>
            </div>
            <br>
            <div v-if="loading" class="loading-state mt-8 text-center py-10">
                <i class="fas fa-spinner fa-spin text-3xl mb-2 text-red-600"></i>
                <p class="text-gray-500 font-medium">Consultando base de datos...</p>
            </div>

            <div v-else-if="error" class="error-message p-4 mb-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                <p class="font-bold"><i class="fas fa-exclamation-triangle"></i> {{ error }}</p>
            </div>

            <div v-else-if="filteredPedidos.length === 0" class="cart-empty-message mt-8 text-center py-12 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">
                <i class="fas fa-info-circle mb-2 text-3xl text-slate-300"></i>
                <p class="text-slate-500 font-medium">No se encontraron pedidos con los filtros aplicados.</p>
                <button @click="resetFilters" class="text-red-600 btn-secondary font-bold hover:underline mt-2">Ver todos los pedidos</button>
            </div>

            <div v-else class="table-container mt-8">
                <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm bg-white animate-fade-in">
    <table class="min-width-full divide-y divide-gray-200 responsive-table">
        <thead class="bg-gray-100 hidden md:table-header-group">
            <tr>
                <th class="table-header w-32">N</th>
                <th class="table-header">Nombre del Cliente</th>
                <th class="table-header text-center w-32">Prioridad</th>
                <th class="table-header">Fecha de creación</th>
                <th class="table-header text-center w-24">Ítems</th>
                <th class="table-header w-40">Estado</th>
                <th class="px-6 py-3 w-28">Detalles</th>
            </tr>
        </thead>
        <tbody class="bg-white bk divide-y divide-gray-100 block md:table-row-group">
            <tr v-for="pedido in filteredPedidos" :key="pedido.id" 
                class="hover:bg-gray-50 transition-colors block md:table-row relative p-5 md:p-0 border-b md:border-none">
                
                <td class="table-cell font-bold bld text-red-800 block md:table-cell" data-label="N">
                    {{ pedido.display_id || pedido.numero_referencia || ('PED-' + pedido.id) }} <br>
                    <br><label v-if="pedido.user" style="color:gray; font-size:9px">{{ pedido.user.full_name }}</label>
                </td>

                <td class="table-cell block md:table-cell" data-label="NOMBRE DELCLIENTE">
                    <div class="text-sm font-bold text-gray-800 text-truncate max-w-cliente" :title="pedido.cliente?.name">
                        {{ pedido.cliente?.name || 'No asignado' }}
                    </div>
                    <div class="text-[10px] text-gray-400 uppercase tracking-tighter mt-1">
                        {{ pedido.cliente?.tipo || 'N/A' }}
                    </div>
                </td>
               
                <td class="table-cell text-left md:text-center block md:table-cell" data-label="PRIORIDAD">
                    <span class="priority-badge bld" :class="getPriorityBadgeClass(pedido.prioridad)">
                        {{ (pedido.prioridad || 'media').toUpperCase() }}
                    </span>
                </td>

                <td class="table-cell text-slate-500 text-xs italic block md:table-cell" data-label="FECHA DE CREACION">
                    {{ formatDate(pedido.created_at) }}
                </td>

                <td class="table-cell text-left md:text-center bld font-bold text-gray-700 block md:table-cell" data-label="ÍTEMS">
                    {{ calculateTotalItems(pedido.detalles) }}
                </td>

                <td class="table-cell block md:table-cell" data-label="ESTADO">
                    <span class="status-badge" :class="getStatusClass(pedido.status)">
                        <i class="fas fa-circle text-[6px] mr-2"></i>
                        {{ pedido.status.toUpperCase() }}
                    </span>
                </td>

                <td class="table-cell text-right block md:table-cell">
                    <router-link :to="{ name: 'DetallePedido', params: { id: pedido.id } }" class="text-red-link flex items-center justify-end gap-1 w-full font-bold text-sm">
                        DETALLES <i class="fas fa-chevron-right ml-1 text-[10px]"></i>
                    </router-link>
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
    </div>
</template>

<style scoped>
/* Estructura de Grid igual a Gastos */
.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    align-items: flex-end;
    
}

@media (min-width: 1024px) {
    .md\:col-span-2 { grid-column: span 2; }
}

/* Estilos de Inputs para que se vean como el de Gastos */
.label-header { 
    @apply text-[10px] uppercase font-black text-slate-400 mb-1.5 block tracking-widest; 
}
.bk{
    background-color: white;
}
.bkk{
     background-color: #fdfdfd; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px;
}
.form-input { 
    padding: 10px 14px; 
    border-radius: 12px; 
    border: 2px solid #f1f5f9; 
    font-weight: 700; 
    color: #334155; 
    background: #fafbfc; 
    transition: all 0.2s; 
    font-size: 0.85rem; 
}

.form-input:focus { 
    border-color: #e4989c; 
    background: white; 
    outline: none; 
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
    font-size: 0.9rem;
}

/* Botones */
.btn-primary { 
    background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); 
    color: white; 
    border-radius: 16px; 
    font-weight: 900; 
    text-transform: uppercase; 
    font-size: 0.75rem; 
    letter-spacing: 0.05em;
    transition: all 0.2s;
}

.btn-primary:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 8px 15px rgba(169, 51, 57, 0.2); 
}

/* Tabla y Badges (Tus estilos originales optimizados) */
.table-header { padding: 14px 16px; font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; text-align: left; }
.table-cell { padding: 14px 16px; font-size: 0.85rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
.status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; display: inline-flex; align-items: center; }
.text-red-link { color: #b91c1c; font-weight: 800; font-size: 0.75rem; }
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

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
.bld{
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

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import axios from '../axios';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const pedidos = ref([]);
const loading = ref(false);
const error = ref(null);
const errorDetail = ref(null);

const currentPage = ref(1);
const lastPage = ref(1);
const totalPedidos = ref(0);

const currentUserRole = ref('');

// FILTROS
const filters = reactive({
    search: '',
    status: '',
    priority: ''
});

const hasFilters = computed(() => {
    return filters.search !== '' || filters.status !== '' || filters.priority !== '';
});

// ── REEMPLAZAR FUNCIÓN: FETCH PEDIDOS CON SOPORTE PARA PAGINACIÓN ──
const fetchPedidos = async (page = 1) => {
    loading.value = true;
    error.value = null;
    currentPage.value = page; // Guardamos la página que se está consultando
    
    try {
        // Enviamos la página actual como parámetro query (?page=1) al backend
        const response = await axios.get('/pedidos', {
            params: { page: page }
        }); 
        // Estructura estándar de Laravel paginate(): response.data.data contiene los registros
        const paginatedData = response.data;
        
        if (paginatedData && typeof paginatedData === 'object' && 'data' in paginatedData) {
            pedidos.value = paginatedData.data;
            currentPage.value = paginatedData.current_page;
            lastPage.value = paginatedData.last_page;
            totalPedidos.value = paginatedData.total;
        } else {
            // Respaldamos en caso de que el controlador devuelva la lista directa sin paginar
            let list = Array.isArray(paginatedData) ? paginatedData : [];
            list.sort((a, b) => b.id - a.id);
            pedidos.value = list;
            lastPage.value = 1;
        }
    } catch (err) {
        error.value = 'No se pudo obtener el listado de pedidos.';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

// Función auxiliar para cambiar de página de forma segura
const changePage = (page) => {
    if (page >= 1 && page <= lastPage.value) {
        fetchPedidos(page);
    }
};

// FILTRADO REACTIVO (Computado)
const filteredPedidos = computed(() => {
    return pedidos.value.filter(p => {
        const matchesSearch = !filters.search || 
            (p.display_id || p.numero_referencia || '').toLowerCase().includes(filters.search.toLowerCase()) ||
            (p.cliente?.name || '').toLowerCase().includes(filters.search.toLowerCase());
            
        const matchesStatus = !filters.status || p.status === filters.status;
        const matchesPriority = !filters.priority || (p.prioridad || '').toLowerCase() === filters.priority.toLowerCase();
        
        return matchesSearch && matchesStatus && matchesPriority;
    });
});

// ── FRAGMENTO A AGREGAR: VALIDACIÓN Y FUNCIÓN DE DESCARGA ──
const canDownload = computed(() => {
    // El botón se habilitará ÚNICAMENTE si el estado seleccionado es 'PENDIENTE'
    return filters.status === 'PENDIENTE';
});

const descargarPedidos = async () => {
    if (!canDownload.value) return;

    try {
        // 1. Enviamos de forma segura por POST los filtros bajo tu Autenticación actual
        const response = await axios.post('/pedidos/descargar', {
            status: 'PENDIENTE',
            priority: filters.priority || null
        });

        if (response.data.success && response.data.download_url) {
            // 2. Creamos un trigger de descarga con la URL que nos fabricó el servidor
            const link = document.createElement('a');
            link.href = response.data.download_url;
            link.setAttribute('download', response.data.file_name);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // 3. Opcional: Le decimos al servidor que borre el archivo temporal para no ocupar espacio
            setTimeout(async () => {
                await axios.delete(`/pedidos/limpiar-descarga/${response.data.file_name}`);
            }, 5000); // Espera 5 segundos a que termine de bajar y lo elimina
        }

    } catch (error) {
        console.error("Error al procesar el archivo en el servidor:", error);
        alert("Hubo un error al generar el reporte en el servidor.");
    }
};

const resetFilters = () => {
    filters.search = '';
    filters.status = '';
    filters.priority = '';
};

// Formateadores y Clases
const formatDate = (dateString) => {
    if (!dateString) return '---';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' });
};

const calculateTotalItems = (detalles) => {
    if (!detalles || !Array.isArray(detalles)) return 0;
    return detalles.reduce((sum, item) => sum + (parseInt(item.cantidad) || 0), 0);
};

const getStatusClass = (status) => {
    const base = 'status-badge ';
    switch (status) {
        case 'ENTREGADO': return base + 'bg-green-100 text-green-700 border border-green-200'; 
        case 'PENDIENTE': return base + 'bg-yellow-100 text-yellow-700 border border-yellow-200';
        case 'EN PROCESO': return base + 'bg-blue-100 text-blue-700 border border-blue-200';
        case 'CANCELADO': return base + 'bg-red-100 text-red-700 border border-red-200'; 
        default: return base + 'bg-slate-100 text-slate-500';
    }
};

const getPriorityBadgeClass = (priority) => {
    const p = (priority || 'media').toLowerCase();
    if (p === 'alta') return 'bg-red-600 text-white shadow-sm';
    if (p === 'media') return 'bg-orange-100 text-orange-700 border border-orange-200';
    return 'bg-slate-100 text-slate-500 border border-slate-200';
};

onMounted(async () => {
    await fetchPedidos();
    
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

