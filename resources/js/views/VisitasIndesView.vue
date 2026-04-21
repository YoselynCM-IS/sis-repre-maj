<template>
    <div class="content-wrapper">
        <div class="module-page">
            <div class="module-header detail-header-flex">
                <div>
                    <h1>Seguimiento de Visitas</h1>
                    <p>Gestiona los prospectos y las interacciones con los planteles de forma directa.</p>
                </div>
                <div class="flex gap-3">
                    <router-link to="/primeras-visitas" class="btn-primary flex-row-centered gap-2">
                        <i class="fas fa-plus-circle"></i> Primera Visita
                    </router-link>
                </div>
            </div>

            <div class="filter-section form-section mt-6">
                <div class="section-title">
                    <i class="fas fa-filter"></i> Filtros y Búsqueda
                </div>
                <div class="filter-grid">
                    <div class="form-group col-span-2">
                        <label for="search">Plantel o Entrevistado:</label>
                        <div class="relative">
                            <i class="fas fa-search search-icon"></i>
                            <input 
                                type="text" 
                                id="search" 
                                v-model="filters.query" 
                                class="form-input pl-10" 
                                placeholder="Plantel o Entrevistado..."
                                @keyup.enter="fetchVisitas"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fechaDesde">Desde:</label>
                        <input 
                            type="date" 
                            id="fechaDesde" 
                            v-model="filters.fecha_inicio" 
                            class="form-input"
                            @change="fetchVisitas"
                        >
                    </div>

                    <div class="form-group">
                        <label for="fechaHasta">Hasta:</label>
                        <input 
                            type="date" 
                            id="fechaHasta" 
                            v-model="filters.fecha_fin" 
                            class="form-input"
                            @change="fetchVisitas"
                        >
                    </div>

                    <div class="form-group">
                        <label for="resultadoStatus">Estado:</label>
                        <select 
                            v-model="filters.resultado" 
                            id="resultadoStatus" 
                            class="form-input"
                            @change="fetchVisitas"
                        >
                            <option value="">Mostrar Todos</option>
                            <option value="seguimiento">Seguimiento</option>
                            <option value="compra">Completados</option>
                            <option value="rechazo">Rechazados</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-100">
                    <button @click="fetchVisitas" class="btn-primary py-2 px-8" :disabled="loading">
                        <i class="fas fa-sync-alt mr-1" :class="{'fa-spin': loading}"></i> 
                        {{ loading ? 'Buscando...' : 'Buscar' }}
                    </button>
                    <br><br>
                    <button @click="resetFilters" class="btn-secondary" title="Limpiar Filtros">
                        <i class="fas fa-eraser mr-1"></i> Borrar Filtros
                    </button>
                    
                </div>
            </div>
            
            <br><br>
            
            <div v-if="loading" class="loading-state py-10 mt-8 text-center">
                <i class="fas fa-spinner fa-spin text-3xl mb-2 text-red-600"></i>
                <p class="text-gray-500 font-medium">Consultando registros...</p>
            </div>

            <div v-else-if="visitas.length === 0" class="cart-empty-message mt-8 text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed">
                <i class="fas fa-info-circle mb-3 text-3xl text-gray-300"></i>
                <p class="text-gray-500 font-medium">No se encontraron visitas que coincidan con los filtros aplicados.</p>
            </div>
            
            <div v-else class="table-responsive table-shadow-lg mt-8 border rounded-xl overflow-hidden shadow-sm bg-white animate-fade-in">
    <table class="min-width-full divide-y divide-gray-200 responsive-table">
        <thead class="bg-gray-100 hidden md:table-header-group">
            <tr>
                <th class="table-header">Fecha</th>
                <th class="table-header">Plantel</th>
                <th class="table-header">Entrevistado</th>
                <th class="table-header text-center">Estado</th>
                <th class="table-header">Próxima visita</th>
                <th class="px-6 py-3 w-28"></th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100 block md:table-row-group">
            <tr v-for="visita in visitas" :key="visita.id" 
                class="hover:bg-gray-50 transition-colors block md:table-row relative p-5 md:p-0 border-b md:border-none">
                
                <td class="table-cell table-cell-bold text-gray-700 block md:table-cell" data-label="FECHA">
                    {{ formatDate(visita.fecha) }}
                    <br><label v-if="visita.user.role == 'representante'" style="color:gray; font-size:9px">{{ visita.user.full_name }}</label>
                </td>
                
                <td class="table-cell block md:table-cell" data-label="PLANTEL">
                    <div class="text-red-900 font-bold uppercase text-xs text-truncate max-w-plantel" 
                        :title="visita.cliente?.name"> {{ visita.cliente?.name || 'Plantel no disponible' }}
                    </div>
                </td>

                <td class="table-cell block md:table-cell" data-label="ENTREVISTADO">
                    <div class="text-sm font-medium text-gray-800 text-truncate max-w-entrevistado" 
                        :title="visita.persona_entrevistada">
                        {{ visita.persona_entrevistada }}
                    </div>
                </td>
                
                <td class="table-cell text-left md:text-center block md:table-cell" data-label="ESTADO">
                    <span class="status-badge" :class="getOutcomeClass(visita.resultado_visita)">
                        <i :class="getOutcomeIcon(visita.resultado_visita)" class="mr-1"></i>
                        {{ (visita.resultado_visita || 'seguimiento').toUpperCase() }}
                    </span>
                </td>

                <td class="table-cell block md:table-cell" data-label="PRÓXIMA VISITA">
                    <div v-if="visita.proxima_visita_estimada" class="text-green-700 font-bold text-sm">
                        <i class="far fa-calendar-alt mr-1"></i>
                        {{ formatDate(visita.proxima_visita_estimada) }}
                    </div>
                    <div v-else class="text-gray-300 text-[10px] italic">No agendada</div>
                </td>

                <td class="table-cell-action text-right block md:table-cell">
                    <button @click="viewDetails(visita)" class="text-red-link font-bold text-sm flex items-center justify-end gap-1 w-full">
                        <i class="fas fa-eye"></i> Ver Detalle
                    </button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const visitas = ref([]);
const loading = ref(true);

const filters = reactive({
    query: '',
    fecha_inicio: '',
    fecha_fin: '',
    resultado: ''
});

const fetchVisitas = async () => {
    loading.value = true;
    try {
        const params = {
            es_primera_visita: 1 // REGLA: Solo primeras visitas (valor 1 en BD)
        };
        
        if (filters.query) params.search = filters.query;
        if (filters.fecha_inicio) params.desde = filters.fecha_inicio;
        if (filters.fecha_fin) params.hasta = filters.fecha_fin;
        if (filters.resultado) params.resultado = filters.resultado;

        const response = await axios.get('/visitas', { params });
        
        // Laravel paginate entrega los registros en data.data o response.data según el controlador
        const dataReceived = response.data.data || response.data;
        
        // REGLA: Ordenar por ID o fecha para que el último agregado aparezca primero 
        // (Aunque el backend lo haga, lo aseguramos aquí)
        let sortedData = Array.isArray(dataReceived) ? dataReceived : [];
        sortedData.sort((a, b) => b.id - a.id);
        
        visitas.value = sortedData;
        
    } catch (err) {
        console.error("Error al cargar visitas:", err);
        visitas.value = [];
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    Object.assign(filters, { query: '', fecha_inicio: '', fecha_fin: '', resultado: '' });
    fetchVisitas();
};

const formatDate = (dateString) => {
    if (!dateString) return '---';
    const cleanDate = dateString.split('T')[0];
    const parts = cleanDate.split('-');
    const date = new Date(parts[0], parts[1] - 1, parts[2]);
    return date.toLocaleDateString('es-ES', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
};

const getOutcomeClass = (outcome) => {
    const base = 'status-badge ';
    if (outcome === 'compra') return base + 'bg-green-50 text-green-700 border-green-100';
    if (outcome === 'rechazo') return base + 'bg-red-50 text-red-700 border-red-100';
    return base + 'bg-orange-50 text-orange-700 border-orange-100';
};

const getOutcomeIcon = (outcome) => {
    if (outcome === 'compra') return 'fas fa-check-circle';
    if (outcome === 'rechazo') return 'fas fa-times-circle';
    return 'fas fa-clock';
};

const viewDetails = (visita) => {
    router.push({ name: 'VisitaDetalle', params: { id: visita.id } });
};

onMounted(fetchVisitas);
</script>

<style scoped>
.filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: flex-end; }
@media (min-width: 1024px) { .col-span-2 { grid-column: span 2; } }
.relative { position: relative; }
.pl-10 { padding-left: 2.5rem; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; z-index: 10; }
.filter-section { background-color: #fdfdfd; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px; }
.table-header { padding: 14px 16px; font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; text-align: left; letter-spacing: 0.025em; }
.table-cell { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
.table-cell-bold { font-weight: 700; }
.text-red-link { color: #b91c1c; background: none; border: none; cursor: pointer; transition: color 0.2s; font-weight: 700; }
.text-red-link:hover { color: #7f1d1d; text-decoration: underline; }
.badge-blue { background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 12px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
.badge-gray { background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 12px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
.status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; display: inline-flex; align-items: center; }
.detail-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
@media (max-width: 768px) { .detail-header-flex { flex-direction: column; align-items: flex-start; gap: 15px; } }
/* Truncado universal para celdas */
.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}

.max-w-plantel { max-width: 200px; }
.max-w-entrevistado { max-width: 150px; }

.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    background-color: white;
}

table {
    table-layout: fixed;
    width: 100%;
    
}

.btn-primary { background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
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
</style>