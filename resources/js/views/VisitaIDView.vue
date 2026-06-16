<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div class="header-info min-w-0">
                    <h1 v-if="loadingPrecarga" class="text-2xl font-black text-slate-300 animate-pulse uppercase">Sincronizando expediente...</h1>
                    <h1 v-else-if="selectedCliente" class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight leading-tight uppercase">
                        Seguimiento: {{ selectedCliente.name }}
                    </h1>
                    <h1 v-else class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight text-red-800 uppercase">Seguimiento de Prospectos</h1>
                    <p class="text-xs md:text-sm text-slate-500 font-medium mt-1 uppercase tracking-tighter italic">Actualiza el expediente y registra los nuevos acuerdos de la visita subsecuente.</p>
                </div>
                <button @click="router.push('/visitas')" class="btn-secondary flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm shrink-0 w-full sm:w-auto bg-white border-2 border-slate-200 text-black uppercase" :disabled="loading">
                    <i class="fas fa-arrow-left mr-2"></i> Volver al Historial
                </button>
            </div>

            <!-- Alerta de Error -->
            <div v-if="errorMessage" class="error-message-container mb-6 p-4 bg-red-50 border border-red-200 rounded-2xl animate-fade-in shadow-sm">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-red-600 mt-1"></i>
                    <div>
                        <p class="text-red-800 font-black uppercase text-[10px] tracking-widest">Atención en el Registro</p>
                        <p class="text-red-600 text-xs mt-1 font-medium whitespace-pre-wrap">{{ errorMessage }}</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="handleSubmit">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- BLOQUE 1: DATOS DEL PLANTEL (IDENTIDAD) -->
                    <div class="form-section shadow-premium border-t-4 border-t-red-700 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                        <div class="section-title label-large text-black">
                            <i class="fas fa-school text-red-700"></i> 1. DATOS DEL PLANTEL
                        </div>
                        
                        <!-- BUSCADOR (Solo si no viene de una precarga) -->
                        <div v-if="!route.params.id" class="form-group relative mb-6">
                            <label class="label-style">Seleccionar Plantel para Seguimiento *</label>
                            <div class="relative">
                                <input v-model="searchQuery" type="text" class="form-input pl-10 font-bold lbb uppercase" placeholder="BUSCAR POR NOMBRE..." @input="searchProspectos" autocomplete="off">
                                <i class="fas fa-university absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            </div>
                            <ul v-if="clientesSuggestions.length" class="autocomplete-list shadow-2xl border border-red-100">
                                <li v-for="v in clientesSuggestions" :key="v.id" @click="selectProspecto(v)" class="hover:bg-red-50 transition-colors border-b last:border-0 border-slate-50">
                                    <div class="flex justify-between items-start">
                                        <div class="text-xs font-black text-slate-800 uppercase line-clamp-1">{{ v.name }}</div>
                                        <span class="text-[7px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded font-black uppercase">{{ v.tipo }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- CAMPOS INFORMATIVOS (BLOQUEADOS) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="form-group md:col-span-2">
                                <label class="label-style">Nombre del Plantel</label>
                                <input v-model="form.plantel.name" type="text" class="form-input font-black bg-slate-50 border-slate-100 uppercase" disabled>
                            </div>
                        </div>
  
                        <div class="form-group mb-6">
                            <label class="label-style">Dirección Completa</label>
                            <select v-model="form.plantel.estado_id" class="form-input font-bold bg-slate-50 border-slate-100 uppercase" disabled>
                                    <option v-for="e in estados" :key="e.id" :value="e.id">{{ e.estado }}</option>
                                </select>
                            <textarea v-model="form.plantel.direccion" class="form-input font-medium bg-slate-50 border-slate-100 uppercase lbb" rows="2" disabled></textarea> 
                        </div>
                        <!-- CAMPO EDITABLE: DIRECTOR / COORDINADOR -->
                        <div class="form-group mt-6 p-6 bg-red-50/30 rounded-[2rem] border-2 border-red-100">
                            <label class="label-style !text-red-700">Nombre del Director / Coordinador *</label>
                            <input v-model="form.plantel.director" type="text" class="form-input font-black uppercase lbb" placeholder="NOMBRE DEL TITULAR" disabled>
                       </div>
                    </div>

                    <!-- BLOQUE 2: DETALLES DE LA NUEVA VISITA -->
                    <div class="space-y-8">
                        <div class="form-section shadow-premium border-t-4 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            <div class="section-title  label-large text-black">
                                <i class="fas fa-calendar-plus  text-slate-800"></i> 2. Detalles de la Interacción
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div class="form-group">
                                    <label class="label-style">Fecha de la Visita *</label>
                                    <input v-model="form.visita.fecha" type="date" class="form-input font-bold lbb" required :disabled="loading">
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Persona Entrevistada *</label>
                                    <input v-model="form.visita.persona_entrevistada" type="text" class="form-input font-bold uppercase lbb" placeholder="¿QUIÉN ATENDIÓ?" required minlength="3" :disabled="loading">
                                </div>
                            </div>

                            <div class="form-group mb-6">
                                <label class="label-style">Cargo / Puesto *</label>
                                <select v-model="form.visita.cargo" class="form-input font-bold uppercase lbb" required :disabled="loading">
                                    <option value="Director/Coordinador">Director/Coordinador</option>
                                    <option value="Subdirector">Subdirector</option>
                                    <option value="Jefe de Departamento">Jefe de Departamento</option>
                                    <option value="Profesor">Profesor</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>

                            <div v-if="form.visita.cargo === 'Otro'" class="form-group mb-6 animate-fade-in">
                                <label class="label-style">Especifique el Cargo *</label>
                                <input v-model="form.visita.cargo_especifico" type="text" class="form-input font-bold uppercase lbb border-red-100" placeholder="ESCRIBA EL PUESTO REAL..." required :disabled="loading">
                            </div>
                        </div>

                        <!-- APARTADO A: LIBROS DE INTERÉS (OPCIONAL) -->
                        <div class="form-section shadow-premium border-t-4 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            <div class="bg-slate-50 p-6 rounded-[2.5rem] border border-slate-100 mb-6 relative lbb" style="overflow: visible !important;">
                                <label class="label-large mb-4 text-slate-600 font-black tracking-tighter uppercase">
                                    <i class="fas fa-eye mr-1 text-blue-500"></i> 3.  Libros de Interés
                                </label>
                                <label class="label-mini uppercase lbb">Buscar Libros de Interes</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4 lbb">
                                    <div class="form-group">
                                        <select v-model="selectedSerieIdA" class="form-input font-bold text-xs lbb" @change="handleSerieChange('interest')">
                                            <option value="">Filtrar por Serie...</option>
                                            <option v-for="s in seriesFiltradas" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                                            <option value="otro">VER TODAS LAS SERIES</option>
                                        </select>
                                    </div>
                                    <div class="form-group relative lbb">
                                        <div class="relative lbb">
                                            <input v-model="interestInput.titulo" type="text" class="form-input pr-10 font-bold lbb uppercase" placeholder="BUSCAR MATERIAL..." @input="searchBooks($event, 'interest')" autocomplete="off">
                                            <i v-if="searchingInterest" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-400 lbb"></i>
                                        </div>
                                        <ul v-if="interestSuggestions.length" class="autocomplete-list shadow-2xl border border-slate-100">
                                            <li v-for="b in interestSuggestions" :key="b.id" @click="addMaterial(b, 'interest')" class="text-[11px] font-black uppercase text-slate-700 hover:bg-red-50 p-3 transition-colors flex justify-between items-center lbb">
                                                <span>{{ b.titulo }}</span>
                                                <span class="text-[7px] bg-slate-100 px-1.5 rounded">{{ b.type }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div v-if="selectedInterestBooks.length" class="table-container mt-6">
                                    <div class="table-responsive border rounded-xl overflow-hidden shadow-sm bg-white lbb">
                                        <table class="w-full divide-y divide-gray-200 lbb">
                                            <thead class="bg-slate-900 text-white lbb">
                                                <tr class="text-[9px] uppercase tracking-widest font-black lbb">
                                                    <th class="px-4 py-3 text-left lbb">Libro</th>
                                                    <th class="px-4 py-3 text-center w-36 lbb">Formato</th>
                                                    <th class="px-4 py-3 w-12 lbb"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 lbb">
                                                <tr v-for="(item, idx) in selectedInterestBooks" :key="idx" class="lbb">
                                                    <td class="table-cell lbb">
                                                        <div class="text-xs font-black text-slate-800 uppercase leading-tight lbb">{{ item.titulo }}</div>
                                                        <div class="text-[9px] font-black text-slate-400 uppercase mt-1 lbb">{{ item.serie_nombre }}</div>
                                                    </td>
                                                    <td class="table-cell text-center lbb">
                                                        <select v-model="item.tipo" class="select-table lbb" :disabled="item.original_type === 'digital'">
                                                            <option value="fisico">FÍSICO</option>
                                                            <option value="digital">DIGITAL</option>
                                                            <option value="paquete">PAQUETE</option>
                                                        </select>
                                                    </td>
                                                    <td class="table-cell text-center">
                                                        <button type="button" @click="selectedInterestBooks.splice(idx, 1)" class="btn-secondary">Quitar<i class="fas fa-trash-alt"></i></button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8 border-2 border-dashed border-slate-200 rounded-3xl lbb">
                                    <p class="text-[10px] font-bold text-slate-300 uppercase italic lbb">Sin materiales de interés agregados</p>
                                </div>
                            </div>
                        </div>

                        <!-- APARTADO B: PROMOCIÓN (OPCIONAL) -->
                        <div class="form-section shadow-premium border-t-4 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            <div class="bg-red-50/30 p-6 rounded-[2.5rem] border border-red-100 relative lbb" style="overflow: visible !important;">
                                <label class="label-large mb-4 text-slate-600 font-black tracking-tighter uppercase">
                                    <i class="fas fa-eye mr-1 text-blue-500"></i> 4.  MUESTRAS DE PROMOCIÓN ENTREGADAS
                                </label>
                                <div class="form-group relative mb-4 lbb">
                                    <label class="label-mini uppercase lbb">Buscar Muestra de promocion</label>
                                    <div class="relative lbb">
                                        <input v-model="deliveredInput.titulo" type="text" class="form-input pr-10 font-bold border-red-100 shadow-sm lbb uppercase" placeholder="BUSCAR MATERIAL PROMOCIÓN..." @input="searchBooks($event, 'delivered')" autocomplete="off">
                                        <i v-if="searchingDelivered" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-400 lbb"></i>
                                    </div>
                                    <ul v-if="deliveredSuggestions.length" class="autocomplete-list shadow-2xl border border-red-100">
                                        <li v-for="b in deliveredSuggestions" :key="b.id" @click="addMaterial(b, 'delivered')" class="text-[11px] font-black uppercase text-slate-700 hover:bg-red-50 p-3 transition-colors lbb">
                                            <span>{{ b.titulo }}</span>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div v-if="selectedDeliveredBooks.length" class="table-container mt-6 lbb">
                                    <div class="table-responsive border rounded-xl overflow-hidden shadow-sm bg-white lbb">
                                        <table class="w-full divide-y divide-gray-200 lbb">
                                            <thead class="bg-red-900 text-white lbb">
                                                <tr class="text-[9px] uppercase tracking-widest font-black lbb">
                                                    <th class="px-4 py-3 text-left lbb">Libro</th>
                                                    <th class="px-4 py-3 text-center w-32 lbb">Cantidad</th>
                                                    <th class="px-4 py-3 w-16 lbb"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-red-50 lbb">
                                                <tr v-for="(item, idx) in selectedDeliveredBooks" :key="idx" class="lbb">
                                                    <td class="table-cell lbb">
                                                        <div class="text-xs font-black text-slate-800 uppercase leading-tight lbb">{{ item.titulo }}</div>
                                                        <div class="text-[8px] font-black text-red-400 uppercase mt-1 lbb">{{ item.serie_nombre }}</div>
                                                    </td>
                                                    <td class="table-cell text-center lbb"><input v-model.number="item.cantidad" type="number" min="1" class="input-table text-center lbb" /></td>
                                                    <td class="table-cell text-center">
                                                        <button type="button" @click="selectedDeliveredBooks.splice(idx, 1)" class="btn-secondary">Quitar<i class="fas fa-trash-alt"></i></button>
                                                    </td>
                                                 </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- RESULTADO Y AGENDA -->
                        <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            <label class="label-large mb-4 text-slate-600 font-black tracking-tighter uppercase">
                                    <i class="fas fa-eye mr-1 text-blue-500"></i> 5.  RESULTADO Y COMENTARIOS DE LA SESIÓN
                                </label>
                            <div class="form-group mb-6">
                                <label class="label-style">Resolución Actual del Prospecto</label>
                                <select v-model="form.visita.resultado_visita" class="form-input font-black uppercase tracking-widest text-slate-700 lbb" required :disabled="loading">
                                    <option value="seguimiento">CONTINUAR SEGUIMIENTO</option>
                                    <option value="compra">DECISIÓN DE COMPRA (Convertir a Cliente)</option>
                                    <option value="rechazo">NO INTERESADO</option>
                                </select>
                            </div>

                    
                            <div class="form-group lbb">
                                <label class="label-style">Comentarios y Acuerdos de la Sesión *</label>
                                <textarea v-model="form.visita.comentarios" class="form-input font-medium uppercase text-xs lbb" rows="4" placeholder="MÍNIMO 20 CARACTERES..." required minlength="20" :disabled="loading"></textarea>
                            </div>
                        </div>

                        <div v-if="form.visita.resultado_visita === 'seguimiento'" class="form-group mb-6 p-6 bg-orange-50 rounded-[2.5rem] border-2 border-orange-100 shadow-inner animate-fade-in lbb">
                            <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                        
                                <label class="label-large mb-4 text-slate-600 font-black tracking-tighter uppercase">
                                    <i class="fas fa-eye mr-1 text-blue-500"></i> 6.  PROXIMO COMPROMISO
                                </label>
                                <label class="text-orange-800 font-black uppercase text-[9px] mb-3 block tracking-widest lbb">Próxima Acción Agendada *</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lbb">
                                    <input v-model="form.visita.proxima_visita" type="date" class="form-input border-orange-200 font-bold lbb" required :disabled="loading">
                                    <select v-model="form.visita.proxima_accion" class="form-input border-orange-200 font-bold lbb" :disabled="loading">
                                        <option value="visita">Visita de Seguimiento</option>
                                        <option value="presentacion">Presentación Académica</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div v-if="form.visita.resultado_visita === 'compra'" class="form-group mb-6 p-6 bg-orange-50 rounded-[2.5rem] border-2 border-orange-100 animate-fade-in shadow-inner">
                            <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                                
                                <label class="label-mini mb-6 text-red-800 label-large font-black tracking-tighter block border-b border-slate-100 pb-3">
                                    <i class="fas fa-box-open mr-1"></i> 6. DATOS PARA COBRANZA
                                </label>     

                                <div class="form-group mb-6 relative">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        
                                        <div class="form-group">
                                            <label class="label-style mb-2 block">Nombre o Razón Social *</label>
                                            <input 
                                                type="text" 
                                                v-model="form.cobranza.nombre" 
                                                required 
                                                class="form-input font-bold"
                                                placeholder="EJ. JUAN PÉREZ O EMPRESA S.A."
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label class="label-style mb-2 block">RFC *</label>
                                            <input 
                                                type="text" 
                                                v-model="form.cobranza.rfc" 
                                                required 
                                                maxlength="13"
                                                class="form-input font-bold"
                                                placeholder="EJ. XAXX010101000"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label class="label-style mb-2 block">Correo Electrónico *</label>
                                            <input 
                                                type="email" 
                                                v-model="form.cobranza.correo" 
                                                required 
                                                class="form-input font-bold"
                                                style="text-transform: none !important;"
                                                placeholder="ejemplo@correo.com"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label class="label-style mb-2 block">Teléfono de Contacto *</label>
                                            <input 
                                                type="text" 
                                                v-model="form.cobranza.telefono" 
                                                required 
                                                minlength="10" 
                                                maxlength="10" 
                                                class="form-input font-bold"
                                                placeholder="MÁXIMO 10 DÍGITOS"
                                            />
                                        </div>

                                        <div class="form-group md:col-span-2">
                                            <label class="label-style mb-2 block">Dirección Fiscal Completa *</label>
                                            <input 
                                                type="text" 
                                                v-model="form.cobranza.direccion" 
                                                required 
                                                class="form-input font-bold"
                                                placeholder="CALLE, NÚMERO, COLONIA, C.P., ESTADO"
                                            />
                                        </div>

                                        <div class="form-group">
                                            <label class="label-style mb-2 block">Método de Pago *</label>
                                            <select 
                                                v-model="form.cobranza.metodo_pago" 
                                                required 
                                                class="form-input font-black uppercase tracking-widest text-slate-700"
                                            >
                                                <option value="" disabled selected>SELECCIONE UNA OPCIÓN</option>
                                                <option value="Deposito en efectivo">DEPOSITO EN EFECTIVO</option>
                                                <option value="Transferencia">TRANSFERENCIA ELECTRONICA</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label class="label-style mb-2 block">Régimen Fiscal *</label>
                                            <select 
                                                v-model="form.cobranza.regimen_fiscal_id" 
                                                required 
                                                class="form-input font-black uppercase tracking-widest text-slate-700"
                                            >
                                                <option value="" disabled selected>SELECCIONE EL RÉGIMEN FISCAL</option>
                                                <option 
                                                    v-for="regimen in regimenesFiscales" 
                                                    :key="regimen.id" 
                                                    :value="regimen.id"
                                                >
                                                    {{ regimen.codigo }} - {{ regimen.descripcion.toUpperCase() }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- BOTONES DE ACCIÓN -->
                <div class="mt-10 flex flex-col md:flex-row justify-end gap-4 border-t border-slate-100 pt-8 pb-20">
                    <button type="button" @click="$router.push('/visitas')" class="btn-secondary px-10 py-4 uppercase font-bold text-xs tracking-widest bg-white border-2 border-slate-200 text-black rounded-2xl shadow-sm lbb" :disabled="loading">Cancelar</button>
                    <button type="submit" class="btn-primary px-20 py-4 shadow-xl shadow-red-900/10 transition-all active:scale-95 lbb" :disabled="loading || !selectedCliente || gettingLocation">
                        <i class="fas lbb" :class="loading ? 'fa-spinner fa-spin mr-2' : 'fa-save mr-2'"></i> 
                        {{ loading ? 'Sincronizando...' : 'Postear Seguimiento' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- MODAL DE ÉXITO -->
        <Teleport to="body">
            <Transition name="modal-pop">
                <div v-if="showSuccessModal" class="modal-overlay-custom">
                    <div class="modal-content-success animate-scale-in">
                        <div class="success-icon-wrapper shadow-lg shadow-green-100"><i class="fas fa-check lbb"></i></div>
                        <h2 class="modal-title-success uppercase lbb">¡Seguimiento Exitoso!</h2>
                        <p class="modal-text-success lbb">La bitácora se guardó correctamente. El plantel ahora figura como <strong>{{ savedClientType }}</strong> activo.</p>
                        <button @click="goToHistory" class="btn-primary w-full mt-8 bg-slate-900 border-none text-white uppercase font-black py-4 tracking-widest text-xs lbb">Regresar al Listado</button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, nextTick } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const route = useRoute();

const loading = ref(false);
const loadingPrecarga = ref(false);
const loadingInitial = ref(true);
const loadingHistory = ref(false);
const gettingLocation = ref(false);
const showSuccessModal = ref(false);
const errorMessage = ref(null);
const attemptedSubmit = ref(false);

const searchingInterest = ref(false);
const searchingDelivered = ref(false);

const estados = ref([]);
const nivelesCatalog = ref([]); 
const allSeries = ref([]); 

const searchQuery = ref('');
const clientesSuggestions = ref([]);
const selectedCliente = ref(null);

const selectedSerieIdA = ref(''); 
const interestSuggestions = ref([]);
const deliveredSuggestions = ref([]);
const selectedInterestBooks = ref([]);
const selectedDeliveredBooks = ref([]);

const interestInput = reactive({ titulo: '' });
const deliveredInput = reactive({ titulo: '' });

let bookTimer = null;
let clientTimer = null;

const form = reactive({
    plantel: { name: '', rfc: '', niveles: [], direccion: '', estado_id: '', telefono: '', email: '', director: '', latitud: null, longitud: null },
    cobranza: {
        nombre: '',
        correo: '',
        telefono: '',
        rfc: '',
        direccion: '',
        metodo_pago: '',
        regimen_fiscal_id: ''
    },
    visita: { fecha: new Date().toISOString().split('T')[0], persona_entrevistada: '', cargo: 'Director/Coordinador', cargo_especifico: '', comentarios: '', resultado_visita: 'seguimiento', proxima_visita: '', proxima_accion: 'visita' }
});

const seriesFiltradas = computed(() => {
    if (form.plantel.niveles.length === 0) return [];
    return allSeries.value.filter(serie => form.plantel.niveles.includes(serie.nivel_educativo_id));
});

const savedClientType = computed(() => form.visita.resultado_visita === 'compra' ? 'Cliente' : 'Prospecto');

const getLocation = () => {
    if (!navigator.geolocation) return alert("Navegador no soporta GPS.");
    gettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
        (p) => { 
            form.plantel.latitud = p.coords.latitude; 
            form.plantel.longitud = p.coords.longitude; 
            gettingLocation.value = false; 
        },
        () => { gettingLocation.value = false; alert("Permiso de GPS denegado."); },
        { enableHighAccuracy: true }
    );
};

const searchProspectos = () => {
    if (searchQuery.value.length < 3) { clientesSuggestions.value = []; return; }
    if (clientTimer) clearTimeout(clientTimer);
    clientTimer = setTimeout(async () => {
        try {
            const res = await axios.get('/search/clientes', { params: { query: searchQuery.value, include_prospectos: true } });
            clientesSuggestions.value = res.data;
        } catch (e) { console.error(e); }
    }, 400);
};

const selectProspecto = (c) => {
    selectedCliente.value = c;
    searchQuery.value = c.name;
    clientesSuggestions.value = [];
    
    // Rellenar la identidad base del cliente/prospecto
    form.plantel.name = c.name;
    form.plantel.rfc = c.rfc || '';
    form.plantel.direccion = c.direccion || '';
    form.plantel.estado_id = c.estado_id || '';
    form.plantel.telefono = c.telefono || '';
    form.plantel.email = c.email || '';
    form.plantel.director = c.contacto || '';
    
    // Mapeo robusto de niveles educativos con normalización de texto
    if (c.nivel_educativo) {
        const nombres = c.nivel_educativo.split(',').map(n => n.trim().toLowerCase());
        form.plantel.niveles = nivelesCatalog.value
            .filter(niv => nombres.includes(niv.nombre.toLowerCase()))
            .map(niv => niv.id);
    } else {
        form.plantel.niveles = [];
    }
    
    form.plantel.latitud = c.latitud || null;
    form.plantel.longitud = c.longitud || null;

    fetchHistorialYAutocompletar(c.id);
};

const fetchHistorialYAutocompletar = async (id) => {
    loadingHistory.value = true;
    try {
        const res = await axios.get(`/visitas`, { params: { search: searchQuery.value } });
        const dataReceived = res.data.data || res.data;
        const historial = Array.isArray(dataReceived) ? dataReceived.filter(v => v.cliente_id === id) : [];
        
        if (historial.length > 0) {
            const ultimaVisita = historial[0];
            
            // AUTORELLENO DE IDENTIDAD (CAMPOS BLOQUEADOS EXCEPTO DIRECTOR):
            form.plantel.rfc = ultimaVisita.rfc_plantel || form.plantel.rfc;
            form.plantel.direccion = ultimaVisita.direccion_plantel || form.plantel.direccion;
            form.plantel.telefono = ultimaVisita.telefono_plantel || form.plantel.telefono;
            form.plantel.email = ultimaVisita.email_plantel || form.plantel.email;
            form.plantel.director = ultimaVisita.director_plantel || form.plantel.director;
            form.plantel.latitud = ultimaVisita.latitud || form.plantel.latitud;
            form.plantel.longitud = ultimaVisita.longitud || form.plantel.longitud;

            // Sincronizar niveles educativos con normalización
            if (!form.plantel.niveles.length && ultimaVisita.nivel_educativo_plantel) {
                const nombres = ultimaVisita.nivel_educativo_plantel.split(',').map(n => n.trim().toLowerCase());
                form.plantel.niveles = nivelesCatalog.value
                    .filter(niv => nombres.includes(niv.nombre.toLowerCase()))
                    .map(niv => niv.id);
            }
        }
    } catch (e) { 
        console.error("Error al jalar historial anterior:", e); 
    } finally { 
        loadingHistory.value = false; 
    }
};

const handleSerieChange = (type) => {
    if (type === 'interest') { interestInput.titulo = ''; interestSuggestions.value = []; }
};

const searchBooks = (event, type) => {
    const val = event.target.value;
    if (val.length < 3) { type === 'interest' ? interestSuggestions.value = [] : deliveredSuggestions.value = []; return; }
    
    type === 'interest' ? searchingInterest.value = true : searchingDelivered.value = true;
    if (bookTimer) clearTimeout(bookTimer);
    
    const serieId = type === 'interest' ? (selectedSerieIdA.value === 'otro' ? null : selectedSerieIdA.value) : null; 

    bookTimer = setTimeout(async () => {
        try {
            const res = await axios.get('search/libros', { params: { query: val, serie_id: serieId } });
            if (type === 'interest') {
                interestSuggestions.value = res.data.filter(b => b.type !== 'promocion');
            } else {
                deliveredSuggestions.value = res.data.filter(b => b.type === 'promocion');
            }
        } catch (e) { console.error(e); } 
        finally { searchingInterest.value = false; searchingDelivered.value = false; }
    }, 400);
};

const addMaterial = (book, type) => {
    const serie = allSeries.value.find(s => s.id == book.serie_id);
    const serieNombre = serie ? (serie.nombre || serie.serie) : 'Sin Serie';

    if (type === 'interest') {
        if (!selectedInterestBooks.value.find(b => b.id === book.id)) {
            selectedInterestBooks.value.push({ 
                id: book.id, titulo: book.titulo, serie_nombre: serieNombre, 
                original_type: book.type, tipo: book.type === 'digital' ? 'digital' : 'fisico' 
            });
        }
        interestInput.titulo = ''; interestSuggestions.value = [];
    } else {
        if (!selectedDeliveredBooks.value.find(b => b.id === book.id)) {
            selectedDeliveredBooks.value.push({ 
                id: book.id, titulo: book.titulo, serie_nombre: serieNombre, cantidad: 1 
            });
        }
        deliveredInput.titulo = ''; deliveredSuggestions.value = [];
    }
};

const handleSubmit = async () => {
    attemptedSubmit.value = true;
    if (!selectedCliente.value) return;

    if (form.plantel.niveles.length === 0) {
        errorMessage.value = "SELECCIONA NIVELES EDUCATIVOS PARA COMPLETAR EL EXPEDIENTE DEL PLANTEL.";
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    errorMessage.value = null;
    loading.value = true;

    try {
        const nivelNombres = nivelesCatalog.value.filter(n => form.plantel.niveles.includes(n.id)).map(n => n.nombre);
        
        const materiales = {
            interes: selectedInterestBooks.value.map(b => ({ titulo: b.titulo, tipo: b.tipo, serie_nombre: b.serie_nombre })),
            entregado: selectedDeliveredBooks.value.map(b => ({ titulo: b.titulo, cantidad: b.cantidad, serie_nombre: b.serie_nombre }))
        };

        const finalCargo = form.visita.cargo === 'Otro' ? form.visita.cargo_especifico : form.visita.cargo;

        const payload = { 
            cliente_id: selectedCliente.value.id,
            plantel: { ...form.plantel, niveles: nivelNombres }, 
            visita: { ...form.visita, cargo: finalCargo, libros_interes: materiales },
            fecha: form.visita.fecha,
            persona_entrevistada: form.visita.persona_entrevistada,
            cargo: finalCargo,
            libros_interes: materiales,
            comentarios: form.visita.comentarios,
            resultado_visita: form.visita.resultado_visita,
            proxima_visita: form.visita.proxima_visita,
            proxima_accion: form.visita.proxima_accion,
            cobranza: form.visita.resultado_visita === 'compra' ? { ...form.cobranza } : null
        };
        
        await axios.post('visitas/seguimiento', payload);
        showSuccessModal.value = true;
    } catch (err) {
        errorMessage.value = err.response?.data?.message || "Error al guardar el seguimiento.";
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } finally { loading.value = false; }
};

const goToHistory = () => { showSuccessModal.value = false; router.push('/visitas'); };

const regimenesFiscales = ref([])

const fetchRegimenesFiscales = async () => {
    try {
        // Hacemos la petición a la nueva ruta dedicada de la API
        const response = await axios.get('/regimenes-fiscales')
        regimenesFiscales.value = response.data
    } catch (error) {
        console.error('Error al cargar los regímenes fiscales:', error)
    }
}

const verificarPrecarga = async () => {
    const idParam = route.params.id;
    if (!idParam) return;
    loadingPrecarga.value = true;
    try {
        const res = await axios.get(`/visitas/${idParam}`);
        const v = res.data;
        if (v.cliente) selectProspecto(v.cliente);
    } catch (e) { console.error(e); } finally { loadingPrecarga.value = false; }
};

onMounted(async () => {
    window.scrollTo({ top: 0, behavior: 'instant' });
    loadingInitial.value = true;
    try {
        const [resEst, resNiv, resSer, resReg] = await Promise.all([
            axios.get('estados'), axios.get('search/niveles'), axios.get('search/series'), axios.get('/regimenes-fiscales')
        ]);
        estados.value = resEst.data;
        nivelesCatalog.value = resNiv.data;
        allSeries.value = resSer.data;
        regimenesFiscales.value = resReg.data;
        await verificarPrecarga();
    } finally { 
        loadingInitial.value = false; 
    }
});
</script>

<style scoped>
.form-section { background: #fff; padding: 30px; border-radius: 32px; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; font-size: 1.1rem; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 1px; }

.label-style { @apply text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 block; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block tracking-widest; }

.form-input { width: 100%; padding: 14px 18px; border-radius: 16px; border: 2px solid #f1f5f9; font-weight: 700; color: #000000; background: #fafbfc; transition: all 0.2s; font-size: 0.9rem; }
.form-input:focus { border-color: #000000; background: white; outline: none; }
.form-input:disabled { background-color: #f8fafc; color: #94a3b8; cursor: not-allowed; border-style: dashed; }

.btn-primary { background: linear-gradient(135deg, #e5a0a3 0%, #881337 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.btn-primary-blue { background: linear-gradient(135deg, #a93339 0%, #ffd0de 100%); color: white; font-weight: 900; cursor: pointer; border: none; border-radius: 16px; }

.autocomplete-list { position: absolute; z-index: 2000; width: 100%; background: white; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.2); max-height: 250px; overflow-y: auto; list-style: none; padding: 10px; margin: 8px 0 0; }
.autocomplete-list li { padding: 12px 16px; cursor: pointer; border-radius: 12px; border-bottom: 1px solid #f8fafc; transition: all 0.2s; }

.modal-overlay-custom { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-content-success { background: white; padding: 45px; border-radius: 40px; width: 90%; max-width: 450px; text-align: center; border: 1px solid #f1f5f9; }
.success-icon-wrapper { width: 80px; height: 80px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 25px; border: 4px solid white; }
.modal-title-success { font-size: 1.75rem; font-weight: 900; color: #000000; margin-bottom: 12px; }
.modal-text-success { color: #64748b; font-size: 0.95rem; line-height: 1.6; font-weight: 500; }

.table-responsive { width: 100%; overflow-x: auto; background: white; border-radius: 20px; }
.table-header { padding: 14px 16px; font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; text-align: left; }
.table-cell { padding: 12px 16px; vertical-align: middle; }

.input-table, .select-table { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 10px; font-weight: 900; color: #1e293b; padding: 6px 8px; text-transform: uppercase; width: 100%; }

.btn-icon-delete { background: none; border: none; color: #cbd5e1; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: color 0.2s; display: inline-flex; align-items: center; gap: 4px; }
.btn-icon-delete:hover { color: #dc2626; }
.btn-icon-delete-simple { background: none; border: none; color: #cbd5e1; font-size: 0.9rem; cursor: pointer; transition: color 0.2s; }
.btn-icon-delete-simple:hover { color: #dc2626; }

.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; display: inline-block; text-transform: uppercase; }

.shadow-premium { box-shadow: 0 15px 35px -10px rgba(0,0,0,0.05); }

.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.label-large { display: block; font-size: 0.79rem; font-weight: 900; text-transform: uppercase; color: #000000; margin-bottom: 6px; letter-spacing: 0.12em; opacity: 0.8; }

select { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 1rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem; appearance: none; }

/* Focus helper */
.lbb:focus { border-color: #000; border-width: 2px; }
</style>