<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado Dinámico -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div class="header-info min-w-0">
                    
                    <h1 v-if="visita" class="text-2xl md:text-4xl font-black text-black tracking-tight leading-tight break-words">
                        {{ visita.nombre_plantel || visita.cliente?.name || 'Sin nombre' }}
                    </h1>
                    <h1 v-else-if="loading" class="text-2xl font-black text-slate-300 animate-pulse uppercase">Obteniendo información...</h1>
                </div>
                <button @click="router.push('/visitas')" class="btn-secondary shadow-sm shrink-0 w-full sm:w-auto">
                    <i class="fas fa-arrow-left mr-2"></i> VOLVER AL LISTADO
                </button>
            </div>

            <!-- Loader de Sistema -->
            <div v-if="loading" class="loading-state py-20 text-center">
                <i class="fas fa-circle-notch fa-spin text-5xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs">Obteniendo información...</p>
            </div>

            <!-- Error de Conexión -->
            <div v-else-if="error" class="error-message-container p-10 text-center bg-red-50 border-2 border-red-100 rounded-[2.5rem] shadow-sm animate-fade-in">
                <i class="fas fa-exclamation-triangle fa-3xl text-red-600 mb-6"></i>
                <h2 class="text-xl font-black text-black uppercase tracking-tighter">Error al obtener información</h2>
                <p class="text-red-600/70 text-sm mt-2 font-medium">{{ error }}</p>
                <button @click="fetchVisitaDetail" class="btn-primary-action mt-6 px-10">Reintentar</button>
            </div>

            <!-- Contenido Principal -->
            <div v-else-if="visita" class="space-y-8 animate-fade-in pb-20">
                
                <!-- 1. IDENTIDAD DEL PLANTEL -->
                <div class="info-card shadow-premium border-t-8 border-t-red-700">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 border-b border-slate-100 pb-4">
                        <div class="section-title !mb-0 !border-none !pb-0">
                            <i class="fas fa-school text-red-700 mr-2"></i> 1. Identidad y Ubicación del Plantel
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 gap-y-6">
                        <!-- Columna Datos Base -->
                        <div class="space-y-6">
                            <div class="data-row">
                                <label class="label-large">Nombre del Plantel</label>
                                <p class="value-text text-xl leading-none uppercase font-black">{{ visita.nombre_plantel || visita.cliente?.name }}</p>
                            </div>

                             <div class="data-row">
                                    <label class="label-large">Estatus</label>
                                    <p class="font-black text-sm value-text uppercase tracking-wider" :class="visita?.cliente?.tipo === 'CLIENTE' ? 'text-green-600' : 'text-red-700'">
                                        {{ visita?.cliente?.tipo || 'PROSPECTO' }}
                                    </p>
                                </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="data-row">
                                    <label class="label-large">RFC del Plantel</label>
                                    <p class="value-text font-mono uppercase tracking-widest">{{ visita.rfc_plantel || visita.cliente?.rfc || 'No registrado' }}</p>
                                </div>

                                 <div class="data-row">
                                <label class="label-large">Ubicación Geográfica</label>
                                <div v-if="visita.latitud" class="flex items-center gap-3 bg-red-50/30 p-4 rounded-2xl border border-red-100 mt-2">
                                    <div class="w-12 h-12 bg-red-700 text-white rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs font-mono font-bold text-red-600 mt-1">{{ visita.latitud }}, {{ visita.longitud }}</p>
                                    </div>
                                    <a :href="`https://www.google.com/maps?q=${visita.latitud},${visita.longitud}`" target="_blank" class="text-[10px] font-black uppercase text-red-700 hover:underline px-3 border-l border-red-100">Ver Mapa</a>
                                </div>
                                <p v-else class="value-text text-slate-300 italic text-sm">Sin coordenadas registradas</p>
                            </div>

                                <div v-if="visita.cliente?.foto_plantel" class="data-row">
                                    <label class="label-large">Fotografía del Plantel</label>
                                    <div class="caja-miniatura-preview mt-2" style="width: 50% !important; height: 50% !important; overflow: hidden !important; display: block !important; border: 2px solid #e2e8f0; border-radius: 12px;">
                                        <img :src="`/storage/${visita.cliente.foto_plantel}`" 
                                            alt="Foto Plantel" 
                                            class="object-cover" 
                                            style="width: 100% !important; height: 100% !important; display: block !important;" />
                                    </div>
                                </div>
                                <div class="data-row">
                                    <label class="label-large">Niveles Educativos</label>
                                    <div class="flex flex-wrap gap-1.5 mt-1">
                                        <span v-for="n in formatLevels(visita.nivel_educativo_plantel || visita.cliente?.nivel_educativo)" :key="n" class="value-text badge-red-outline">
                                          -  {{ n }}
                                            <br>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="data-row">
                                    <label class="label-large">País / Estado</label>
                                    <p class="value-text uppercase">{{ visita.estado?.pais.nombre }} / {{ visita.estado?.estado || 'No especificado' }}</p>
                                </div>
                                <div class="data-row">
                                    <label class="label-large">Dirección Completa</label>
                                    <p class="value-text uppercase">{{ visita.direccion_plantel|| 'No especificado' }}</p>
                                </div>
                            </div>
                        </div>  
                        <br>

                        <!-- Columna Contacto -->
                        <div class="space-y-6">
                            <div class="data-row">
                                <label class="label-large">Teléfono Principal</label>
                                <p class="value-text tracking-tighter"><i class="fas fa-phone-alt mr-2 opacity-30"></i>{{ visita.telefono_plantel || visita.cliente?.telefono || 'N/A' }}</p>
                            </div>
                            <div class="data-row mt-6">
                                <label class="label-large">Extensión</label>
                                <p class="value-text tracking-tighter"><i class="fas fa-phone-volume mr-2 opacity-30"></i>{{ visita.cliente?.extension || 'N/A' }}</p>
                            </div>
                            <div class="data-row mt-6">
                                <label class="label-large">Teléfono Oficina</label>
                                <p class="value-text tracking-tighter"><i class="fas fa-phone mr-2 opacity-30"></i>{{ visita.cliente?.tel_oficina || 'N/A' }}</p>
                            </div>
                          <div class="data-row">
                                    <label class="label-large">Correo Electrónico</label>
                                    <p class="value-text text-sm" style="text-transform: none !important;">
                                        <i class="fas fa-envelope mr-2 opacity-30"></i>
                                        {{ (visita.email_plantel || visita.cliente?.email || 'N/A').toLowerCase() === 'n/a' ? 'N/A' : (visita.email_plantel || visita.cliente?.email).toLowerCase() }}
                                    </p>
                                </div>
                            <div class="data-row">
                                <label class="label-large">Nombre del Director / Coordinador</label>
                                <p class="value-text italic leading-relaxed text-sm">{{ visita.director_plantel || visita.cliente?.contacto || 'Sin director registrado' }}</p>
                            </div>
                            <div v-if="visita?.cliente?.tipo === 'CLIENTE' && visita.cliente?.cobranzas?.length" class="data-row mt-6 pt-6 border-t border-dashed border-slate-200 animate-fade-in">
                                <label class="label-large !text-red-700 font-black tracking-widest text-[10px] mb-4 flex items-center gap-2 uppercase">
                                    <i class="fas fa-hand-holding-usd"></i> Cobranza
                                </label>
                                <button 
                                    v-if="visita?.cliente?.tipo === 'CLIENTE'" 
                                    type="button"
                                    @click="openCobranzaModal"
                                    class="btn-secondary flex items-center gap-2 shrink-0 !border-slate-300 hover:bg-slate-50 transition-colors mb-2"
                                > AÑADIR INFORMACIÓN
                                </button>

                                <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm bg-white">
                                    <table class="min-width-full divide-y divide-gray-200 responsive-table">
                                        <thead class="bg-gray-100 hidden md:table-header-group">
                                            <tr>
                                                <th class="table-header">N.</th>
                                                <th class="table-header">Nombre o razón social</th>
                                                <th class="table-header">RFC</th>
                                                <th class="table-header">Correo</th>
                                                <th class="table-header">Teléfono de contacto</th>
                                                <th class="table-header">Creado el</th>
                                                <th class="table-header text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white bk divide-y divide-gray-100 block md:table-row-group">
                                            <tr v-for="(item, index) in visita.cliente.cobranzas" :key="item.id" class="border-b border-slate-50 hover:bg-slate-50/50 transition-colors">
                                                <td class="table-cell text-left md:text-center block md:table-cell" data-label="N.">
                                                    <span class="status-badge bg-blue-50 text-blue-700 border border-blue-100">
                                                        {{ index + 1 }}
                                                    </span>
                                                </td>
                                                <td class="table-cell block md:table-cell" data-label="Nombre o razón social">
                                                    <div class="text-sm font-bold text-gray-800 uppercase leading-tight">
                                                        {{ item.responsable || '—' }}
                                                    </div>
                                                </td>
                                                <td class="table-cell block md:table-cell" data-label="RFC">
                                                    <div class="text-sm font-bold text-gray-800 uppercase leading-tight">
                                                        {{ item.rfc }}
                                                    </div>
                                                </td>
                                                <td class="table-cell block md:table-cell" data-label="Correo">
                                                    <div class="text-sm font-bold text-gray-800 uppercase leading-tight">
                                                        {{ item.correo || '—' }}
                                                    </div>
                                                </td>
                                                <td class="table-cell block md:table-cell" data-label="Teléfono de contacto">
                                                    <div class="text-sm font-bold text-gray-800 uppercase leading-tight">
                                                        {{ item.telefono || '—' }}
                                                    </div>
                                                </td>
                                                <td class="table-cell block md:table-cell" data-label="Creado el">
                                                    <div class="text-sm font-bold text-gray-800 uppercase leading-tight">
                                                        {{ item.created_at ? new Date(item.created_at).toLocaleDateString('es-MX', { year: 'numeric', month: '2-digit', day: '2-digit' }) : '—' }}
                                                    </div>
                                                </td>
                                                <td class="table-cell text-left md:text-center block md:table-cell">
                                                    <button 
                                                        @click="abrirDetalleCobranza(item)"
                                                        type="button"
                                                        class="btn-primary flex items-center gap-2 shrink-0 !border-slate-300 hover:bg-slate-50 transition-colors"
                                                    >
                                                        <i class="fas fa-eye mr-1"></i> Detalle
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BENEFICIOS POR CLIENTE -->
                <div class="info-card space-y-6 mt-16">
                    <div class="flex items-center gap-3 px-2">
                        <div class="w-2 h-8 bg-red-700 rounded-full"></div>
                        <div class="section-title text-black !border-black/5">
                            <i class="fas fa-gift text-black"></i> 2. Beneficios para el Cliente
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="bg-amber-50 p-8 rounded-3xl border border-amber-100 italic text-slate-700 text-sm leading-relaxed font-medium shadow-inner uppercase">
                            {{ visita.cliente?.beneficios_adicionales || 'SIN BENEFICIOS ADICIONALES REGISTRADOS PARA ESTE CLIENTE.' }}
                        </div>
                    </div>
                </div>

                <!-- 2. HISTORIAL CRONOLÓGICO -->
                <div class="info-card space-y-6 mt-16">
                    <div class="flex items-center gap-3 px-2">
                        <div class="w-2 h-8 bg-red-700 rounded-full"></div>
                        <div class="section-title text-black !border-black/5">
                            <i class="fas fa-handshake text-black"></i> 3. Historial de Visitas
                        </div>
                    </div>

                    <div v-if="loadingHistory" class="py-10 text-center animate-pulse">
                        <i class="fas fa-spinner fa-spin text-red-600 text-3xl"></i>
                        <p class="text-[10px] font-black text-slate-400 uppercase mt-4">Obteniendo información de seguimiento...</p>
                    </div>

                    <div v-else-if="historial.length" class="space-y-4">
                        <div v-for="(h, index) in historial" :key="h.id" class="border border-slate-100 rounded-3xl overflow-hidden shadow-sm relative group bg-white">
                            <!-- Header de la tarjeta -->
                            <div class="p-6 md:p-8 flex flex-col md:flex-row justify-between items-center gap-6 transition-colors">
                                <div class="flex items-center gap-6 w-full md:w-auto">
                                    <div class="min-w-0">
                                        <p class="text-[8px] label-large font-black uppercase tracking-[0.2em] mb-1" :class="h.es_primera_visita ? 'text-blue-600' : 'text-purple-600'">
                                            Fecha de la visita
                                        </p>
                                        <h4 class="text-xl font-black text-black uppercase tracking-tight truncate max-w-[200px] md:max-w-none">
                                            {{ formatDate(h.fecha) }}
                                        </h4>
                                         <p class="label-large"><i class="fas fa-comment-dots text-red-700 label-large"></i> Estatus</p>
                                            <span :class="getOutcomeClass(h.resultado_visita)" class="status-badge !px-5 !py-2 uppercase shadow-sm">
                                        {{ h.resultado_visita }}
                                    </span>
                                         <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="label-large">Persona Entrevistada</label>
                                                <p class="value-text">{{ h.persona_entrevistada }}</p>
                                            </div>
                                            <div>
                                                <label class="label-large">Cargo/Puesto</label>
                                                <p class="value-text">{{ h.cargo }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto justify-between md:justify-end">
                                    <button 
                                        v-if="(h.modificaciones_realizadas || 0) < 1"
                                        @click="router.push({ name: 'VisitaEdit', params: { id: h.id } })"
                                        class="btn-secondary hover:scale-105 transition-all"
                                    >
                                        <i class="fas fa-edit mr-1"></i> MODIFICAR
                                    </button>

                                    <button 
                                        @click="toggleExpand(h.id)"
                                        class="btn-primary !border-red-600 !text-red-700 hover:bg-red-50 hover:scale-105 transition-all"
                                    >
                                        <i class="fas" :class="expandedId === h.id ? 'fa-eye-slash' : 'fa-plus-circle'"></i>
                                        <span class="ml-2">{{ expandedId === h.id ? 'OCULTAR' : 'VER DETALLE' }}</span>
                                    </button>
                                    
                                    <div @click="toggleExpand(h.id)" class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-300 cursor-pointer hover:text-red-600 transition-colors">
                                        <i class="fas fa-chevron-down transition-transform duration-500" 
                                           :class="{'rotate-180 text-red-600': expandedId === h.id}"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Contenido Desplegable (Expediente) -->
                            <div v-if="expandedId === h.id" class="p-8 md:p-12 bg-slate-50/40 border-t border-slate-100 animate-fade-in">
                                <div class="space-y-12">
                                    
                                    <!-- SECCIÓN DE MATERIALES: VISIBILIDAD CONDICIONAL -->
                                    <div v-if="parseMateriales(h.libros_interes)?.interes?.length || parseMateriales(h.libros_interes)?.entregado?.length">
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                            <div class="space-y-8 animate-fade-in">
                                                <div v-if="parseMateriales(h.libros_interes)?.interes?.length" class="table-container">
                                                    <h5 class="text-black font-black label-large uppercase text-[11px] tracking-[0.2em] mb-6 flex items-center gap-2">
                                                        <i class="fas fa-book-open text-red-700"></i> Libros de interés
                                                    </h5>
                                                    <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm bg-white">
                                                        <table class="min-width-full divide-y divide-gray-200 responsive-table">
                                                            <thead class="bg-gray-100 hidden md:table-header-group">
                                                                <tr>
                                                                    <th class="table-header">Libro</th>
                                                                    <th class="table-header text-center w-28">Formato</th>
                                                                    <th class="table-header text-center w-40">Opción Comercial</th>
                                                                    <th class="table-header text-right w-32">Cantidad / Valor</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="bg-white bk divide-y divide-gray-100 block md:table-row-group">
                                                                <tr v-for="(item, i) in parseMateriales(h.libros_interes).interes" :key="i" 
                                                                    class="hover:bg-gray-50 transition-colors block md:table-row relative p-4 md:p-0 border-b md:border-none">
                                                                    
                                                                    <td class="table-cell block md:table-cell" data-label="LIBRO">
                                                                        <div class="text-sm font-bold text-gray-800 uppercase leading-tight">
                                                                            {{ item.titulo }}
                                                                        </div>
                                                                    </td>
                                                                    
                                                                    <td class="table-cell text-left md:text-center block md:table-cell" data-label="FORMATO">
                                                                        <span class="status-badge bg-blue-50 text-blue-700 border border-blue-100">
                                                                            {{ (item.tipo || 'Físico').toUpperCase() }}
                                                                        </span>
                                                                    </td>

                                                                    <td class="table-cell text-left md:text-center block md:table-cell" data-label="OPCIÓN COMERCIAL">
                                                                        <span class="text-xs font-bold text-gray-700 uppercase">
                                                                            {{ item.beneficio_tipo || 'N/A' }}
                                                                        </span>
                                                                    </td>

                                                                    <td class="table-cell text-left md:text-right block md:table-cell" data-label="CANTIDAD / VALOR">
                                                                        <span class="text-sm font-black text-gray-900">
                                                                            {{ item.beneficio_tipo === 'Descuento por libro' ? (item.beneficio_valor + '%') : (item.beneficio_valor ? ('$' + item.beneficio_valor) : 'N/A') }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>

                                                <div v-if="parseMateriales(h.libros_interes)?.entregado?.length" class="table-container">
                                                    <h5 class="text-black font-black label-large uppercase text-[11px] tracking-[0.2em] mb-6 flex items-center gap-2">
                                                        <i class="fas fa-box-open text-red-700"></i> Muestras de promoción entregadas
                                                    </h5>
                                                    <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm bg-white border-red-100">
                                                        <table class="min-width-full divide-y divide-gray-200 responsive-table">
                                                            <thead class="bg-red-50 hidden md:table-header-group">
                                                                <tr>
                                                                    <th class="table-header !text-red-800">Libro</th>
                                                                    <th class="table-header text-right w-32 !text-red-800">Cantidad</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="bg-white bk divide-y divide-red-50 block md:table-row-group">
                                                                <tr v-for="(item, i) in parseMateriales(h.libros_interes).entregado" :key="i" 
                                                                    class="hover:bg-red-50/30 transition-colors block md:table-row relative p-4 md:p-0 border-b md:border-none">
                                                                    
                                                                    <td class="table-cell block md:table-cell" data-label="LIBRO">
                                                                        <div class="text-sm font-bold text-gray-800 uppercase leading-tight">
                                                                            {{ item.titulo }}
                                                                        </div>
                                                                    </td>
                                                                    
                                                                    <td class="table-cell text-left md:text-right block md:table-cell" data-label="CANTIDAD">
                                                                        <span class="text-sm font-black text-gray-900">
                                                                            {{ item.cantidad }}
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- OBSERVACIONES -->
                                    <div class="space-y-4">
                                        <h5 class="text-black font-black uppercase text-[11px] label-large tracking-widest flex items-center gap-2">
                                            <i class="fas fa-comment-dots text-red-700 label-large"></i> COMENTARIOS Y ACUERDOS DE LA VISTA
                                        </h5>
                                        <div class="bg-amber-50 p-8 rounded-3xl border border-amber-100 italic text-slate-700 text-sm leading-relaxed font-medium shadow-inner">
                                            {{ h.comentarios || 'El representante no dejó observaciones escritas en esta sesión.' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br><br>
                        </div>
                        
                    </div>
                    

                    <div v-else class="text-center py-20 bg-white rounded-[3rem] border-2 border-dashed border-slate-100 opacity-60">
                        <i class="fas fa-history text-4xl text-slate-200 mb-4 block"></i>
                        <p class="text-slate-400 font-bold uppercase text-[10px] tracking-widest italic">Aún no se ha registrado seguimiento.</p>
                    </div>
                </div>

                <!-- 3. PRÓXIMO COMPROMISO (VISIBILIDAD BASADA EN EL ÚLTIMO RESULTADO) -->
                <div v-if="ultimoResultado === 'seguimiento'" class="info-card border-none bg-slate-100 p-10 rounded-[3rem] border border-slate-200 shadow-sm mt-8 text-center animate-fade-in">
                    <div class="flex flex-col items-center gap-6">
                        <div class="section-title text-black !border-black/5 !mb-0">
                            <i class="fas fa-calendar-alt text-black"></i> 4. Próximo Compromiso 
                        </div>
                        
                        <div v-if="proximoCompromisoFinal" class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-200 w-full max-w-lg mx-auto">
                            <label class="label-large !text-red-700">Fecha Agendada</label>
                            <p class="text-4xl font-black text-black tracking-tighter mt-2">{{ formatDate(proximoCompromisoFinal.fecha) }}</p>
                            <div class="flex items-center justify-center gap-2 mt-4 bg-red-700 text-white p-3 rounded-2xl">
                                <p class="label-large mb-0"><i class="fas fa-bullseye text-sm mr-2"></i>Objetivo</p>
                                <span class="text-[11px] font-black uppercase tracking-widest">{{ proximoCompromisoFinal.accion }}</span>
                            </div>
                        </div>
                        
                        <p v-else class="text-slate-400 italic text-sm">Sin fecha programada</p>

                        <button v-if="visita.cliente?.tipo !== 'CLIENTE'" 
                            @click="router.push({ name: 'SeguimientoID', params: { id: visita.id } })" 
                            class="w-full max-w-md btn-primary-action shadow-2xl transition-all active:scale-95 mx-auto">
                            <i class="fas fa-plus-circle mr-2 "></i> Registrar Seguimiento
                        </button>
                    </div>
                </div>

                <!-- 4. BITÁCORA DE AJUSTES TÉCNICOS (Auditoría) - VISIBILIDAD CONDICIONAL -->
                <div v-if="allLogs.length > 0" class="info-card shadow-premium border-t-8 border-t-slate-800 bg-white p-0 rounded-[2.5rem] border border-slate-100 overflow-hidden mt-16">
                    <div class="p-8 border-b border-slate-50 flex items-center justify-between bg-white">
                        <div class="section-title text-black !border-black/5">
                            <i class="fas fa-handshake text-black"></i> 5. Modificaciones
                        </div>
                    </div>

                    <div class="table-container mt-4 animate-fade-in">
                        <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm bg-white animate-fade-in">
    <table class="min-width-full divide-y divide-gray-200 responsive-table">
        <thead class="bg-gray-100 hidden md:table-header-group">
            <tr>
                <th class="table-header w-64">Visita</th>
                <th class="table-header">Motivo de la Modificación</th>
                <th class="table-header w-56">Responsable</th>
                <th class="table-header text-right w-48">Fecha</th>
            </tr>
        </thead>
        <tbody class="bg-white bk divide-y divide-gray-100 block md:table-row-group">
            <tr v-for="(log, index) in allLogs" :key="log.id" 
                class="hover:bg-gray-50 transition-colors block md:table-row relative p-5 md:p-0 border-b md:border-none">
                
                <td class="table-cell block md:table-cell" data-label="VISITA">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black text-red-800 uppercase tracking-tighter">
                            {{ log.visit_type === 'primera' ? 'Primera Visita' : 'Seguimiento' }}
                        </span>
                        <br>
                        <span class="text-[10px] font-bold text-gray-400 uppercase mt-1 italic">
                            {{ formatDateShort(log.visit_date) }}
                        </span>
                    </div>
                </td>

                <td class="table-cell block md:table-cell" data-label="MOTIVO DE LA MODIFICACIÓN">
                    <p class="text-[11px] font-bold text-slate-700 italic leading-relaxed uppercase">
                        "{{ log.motivo_cambio || 'SIN MOTIVO' }}"
                    </p>
                </td>

                <td class="table-cell block md:table-cell" data-label="RESPONSABLE">
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-black text-gray-800 uppercase tracking-tight">
                            {{ log.user?.full_name || 'Representante' }}
                        </span>
                    </div>
                </td>

                <td class="table-cell block md:table-cell text-left md:text-right" data-label="Fecha">
                    <div class="flex flex-col items-start md:items-end">
                        <span class="text-[11px] font-black text-gray-800 uppercase leading-none">
                            {{ formatDateOnly(log.created_at) }}
                        </span>
                        <br>
                        <span class="text-[9px] font-bold text-gray-400 mt-1 tracking-tighter uppercase">
                            {{ formatTimeOnly(log.created_at) }}
                        </span>
                    </div>
                </td>
            </tr>
            <tr v-if="!allLogs || allLogs.length === 0" class="block md:table-row">
                <td colspan="4" class="px-6 py-12 text-center italic text-slate-300 font-black text-[10px] uppercase tracking-widest block md:table-cell">
                    No se han registrado movimientos.
                </td>
            </tr>
        </tbody>
    </table>
</div>
                    </div>
                </div>
            </div>

            <div v-if="addCobranzaModal" class="custom-modal-backdrop" style="overflow-y: auto !important; padding: 20px 10px; display: flex; align-items: start; justify-content: center;">
                <div class="custom-modal-window bg-white border-t-8 border-t-red-700 shadow-premium" style="max-width: 55% !important; margin: auto; height: auto !important;">
                    <div class="custom-modal-content">       
                        <div class="bg-white rounded-[2rem] shadow-2xl border border-slate-100 max-w-2xl p-6 md:p-8 transform transition-all relative" style="width: 100% !important; max-width: 100% !important;">
                            <div class="flex justify-between items-center border-b pb-4 mb-6">
                                <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight" style="color: #0f172a !important;">
                                    <i class="fas fa-file-invoice-dollar text-red-700 mr-2"></i> Datos para cobranza
                                </h3>
                            </div>
                            <form @submit.prevent="saveCobranza">
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
                                                minlength="8" 
                                                maxlength="11" 
                                                class="form-input font-bold"
                                                placeholder="MÁXIMO 11 DÍGITOS"
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
                                            <label class="label-style mb-2 block">Tipo de Pago *</label>
                                            <select 
                                                v-model="form.cobranza.tipo_pago" 
                                                required 
                                                class="form-input font-black uppercase tracking-widest text-slate-700"
                                            >
                                                <option value="" disabled selected>SELECCIONE UNA OPCIÓN</option>
                                                <option value="pago cie">PAGO CIE</option>
                                                <option value="venta directa">VENTA DIRECTA</option>
                                                <option value="escuela">ESCUELA</option>
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

                                        <div class="form-group">
                                            <label class="label-style mb-2 block">Uso de CFDI *</label>
                                            <select 
                                                v-model="form.cobranza.uso_cfdi_id" 
                                                required 
                                                class="form-input font-black uppercase tracking-widest text-slate-700"
                                            >
                                                <option value="" disabled selected>SELECCIONE EL USO DE CFDI</option>
                                                <option 
                                                    v-for="uso in usosCfdiCatalog" 
                                                    :key="uso.id" 
                                                    :value="uso.id"
                                                >
                                                    {{ uso.c_UsoCFDI }} - {{ uso.descripcion.toUpperCase() }}
                                                </option>
                                            </select>
                                        </div>

                                    </div>
                                </div>
                                <div class="mt-8 pt-4 border-t flex justify-end">
                                    <button 
                                        @click="addCobranzaModal = false" 
                                        type="button" 
                                        class="btn-secondary flex items-center gap-2 shrink-0 !border-slate-300 hover:bg-slate-50 transition-colors"
                                    > Cancelar
                                    </button>
                                    
                                    <button 
                                        type="submit" 
                                        :disabled="savingCobranza"
                                        class="btn-primary px-20 py-4 shadow-xl shadow-red-900/10 transition-all active:scale-95"
                                    >
                                        <i v-if="savingCobranza" class="fas fa-spinner animate-spin"></i>
                                        {{ savingCobranza ? 'Guardando...' : 'Guardar Información' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="showCobranzaModal" class="custom-modal-backdrop" style="overflow-y: auto !important; padding: 20px 10px; display: flex; align-items: start; justify-content: center;">
                <div class="custom-modal-window bg-white border-t-8 border-t-red-700 shadow-premium" style="max-width: 55% !important; margin: auto; height: auto !important;">
                    <div class="custom-modal-content">       
                        <div class="bg-white rounded-[2rem] shadow-2xl border border-slate-100 max-w-2xl p-6 md:p-8 transform transition-all relative" style="width: 100% !important; max-width: 100% !important;">
                            <div class="flex justify-between items-center border-b pb-4 mb-6">
                                <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight" style="color: #0f172a !important;">
                                    <i class="fas fa-file-invoice-dollar text-red-700 mr-2"></i> Información Completa de Cobranza
                                </h3>
                            </div>

                            <div v-if="selectedCobranza" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="label-large">Responsable / Razón Social</label>
                                    <p class="text-sm font-bold text-slate-800 uppercase mt-0.5">{{ selectedCobranza.responsable || '—' }}</p>
                                </div>
                                <div>
                                    <label class="label-large">RFC</label>
                                    <p class="text-sm font-bold text-slate-800 uppercase mt-0.5">{{ selectedCobranza.rfc || '—' }}</p>
                                </div>
                                <div>
                                    <label class="label-large">Correo electrónico</label>
                                    <p class="text-sm font-bold text-slate-800 mt-0.5">{{ selectedCobranza.correo || '—' }}</p>
                                </div>
                                <div>
                                    <label class="label-large">Teléfono</label>
                                    <p class="text-sm font-bold text-slate-800 mt-0.5">{{ selectedCobranza.telefono || '—' }}</p>
                                </div>
                                <div>
                                    <label class="label-large">Método de Pago</label>
                                    <p class="text-sm font-bold text-slate-800 uppercase mt-0.5">{{ selectedCobranza.metodo_pago || '—' }}</p>
                                </div>
                                <div>
                                    <label class="label-large">Tipo de Pago</label>
                                    <p class="text-sm font-bold text-slate-800 uppercase mt-0.5">
                                        <span v-if="selectedCobranza.tipo_pago" class="px-2 py-0.5 bg-slate-100 text-slate-800 rounded text-xs">
                                            {{ selectedCobranza.tipo_pago }}
                                        </span>
                                        <span v-else>—</span>
                                    </p>
                                </div>
                                <div>
                                    <label class="label-large">Régimen Fiscal</label>
                                    <p class="text-sm font-bold text-slate-800 mt-0.5">
                                        {{ selectedCobranza.regimen_fiscal.codigo }} - {{ selectedCobranza.regimen_fiscal.descripcion.toUpperCase() }}
                                    </p>
                                </div>
                                <div>
                                    <label class="label-large">Uso de CFDI (ID)</label>
                                    <p class="text-sm font-bold text-slate-800 mt-0.5">
                                        {{ selectedCobranza.uso_cfdi.c_UsoCFDI }} - {{ selectedCobranza.uso_cfdi.descripcion.toUpperCase() }}
                                    </p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="label-large">Dirección Fiscal</label>
                                    <p class="text-sm font-bold text-slate-800 uppercase mt-0.5">{{ selectedCobranza.direccion || '—' }}</p>
                                </div>
                                <div>
                                    <label class="label-large">Fecha de registro</label>
                                    <p class="text-sm font-bold text-slate-800 mt-0.5">
                                        {{ selectedCobranza.created_at ? new Date(selectedCobranza.created_at).toLocaleString('es-MX') : '—' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-8 pt-4 border-t flex justify-end">
                                <button 
                                    @click="showCobranzaModal = false" 
                                    type="button" 
                                    class="btn-secondary flex items-center gap-2 shrink-0 !border-slate-300 hover:bg-slate-50 transition-colors"
                                >
                                    Cerrar Ventana
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import axios from '../axios';

const route = useRoute();
const router = useRouter();
const visita = ref(null);
const historial = ref([]);
const loading = ref(true);
const loadingHistory = ref(true);
const error = ref(null);
const expandedId = ref(null);

const addCobranzaModal = ref(false);
const showCobranzaModal = ref(false);
const selectedCobranza = ref(null);

const regimenesFiscales = ref([]);
const usosCfdiCatalog = ref([]);

const form = reactive({
    cliente_id: null,
    cobranza: {
        id: null,
        nombre: '',
        correo: '',
        telefono: '',
        rfc: '',
        direccion: '',
        metodo_pago: '',
        tipo_pago: '',
        regimen_fiscal_id: '',
        uso_cfdi_id: ''
    }
});

const savingCobranza = ref(false);

const openCobranzaModal = async () => {
    // 1. Asignamos el cliente_id desde la información de la visita actual
    form.cliente_id = visita.value?.cliente_id || visita.value?.cliente?.id || null;
    
    // 2. CORRECCIÓN: Inicializamos usando los nombres exactos que usan tus v-model en el HTML
    form.cobranza = {
        id: visita.value?.cobranza?.id || null,
        nombre: visita.value?.cobranza?.responsable || '', // v-model="form.cobranza.nombre"
        correo: visita.value?.cobranza?.correo || '',       // v-model="form.cobranza.correo"
        telefono: visita.value?.cobranza?.telefono || '',   // v-model="form.cobranza.telefono"
        rfc: visita.value?.cobranza?.rfc || '',             // v-model="form.cobranza.rfc"
        direccion: visita.value?.cobranza?.direccion || '', // v-model="form.cobranza.direccion"
        metodo_pago: visita.value?.cobranza?.metodo_pago || '', // v-model="form.cobranza.metodo_pago"
        tipo_pago: visita.value?.cobranza?.tipo_pago || '',     // v-model="form.cobranza.tipo_pago"
        regimen_fiscal_id: visita.value?.cobranza?.regimen_fiscal_id || '', // v-model="form.cobranza.regimen_fiscal_id"
        uso_cfdi_id: visita.value?.cobranza?.uso_cfdi_id || ''             // v-model="form.cobranza.uso_cfdi_id"
    };

    // 3. Mostramos el modal inmediatamente
    addCobranzaModal.value = true;

    // 4. Cargamos los catálogos desde la base de datos de manera asíncrona
    try {
        const [resRegimenes, resUsos] = await Promise.all([
            axios.get('/regimenes-fiscales'),
            axios.get('/usos-cfdi')
        ]);

        regimenesFiscales.value = resRegimenes.data;
        usosCfdiCatalog.value = resUsos.data;
    } catch (error) {
        console.error("Error al cargar los catálogos de cobranza:", error);
    }
};


// FUNCIÓN PARA GUARDAR DATOS DE COBRANZA ──
const saveCobranza = async () => {
    // Evitamos enviar si ya se está procesando o si no hay un cliente_id válido
    if (savingCobranza.value) return;
    if (!form.cliente_id) {
        Swal.fire({
            icon: 'error',
            title: 'Error de validación',
            text: 'No se encontró un ID de cliente asociado a esta visita.'
        });
        return;
    }

    savingCobranza.value = true;

    try {
        // Enviamos los datos reactivos del formulario al backend
        const response = await axios.post('/cobranzas', form);

        if (response.data) {
            Swal.fire({
                icon: 'success',
                title: '¡Guardado con éxito!',
                text: 'La información de cobranza se ha registrado correctamente.',
                timer: 2000,
                showConfirmButton: false
            });

            // Actualizamos el objeto local de la visita para que los cambios se reflejen
            // en tu otro modal (el de visualización) inmediatamente.
            if (visita.value) {
                visita.value.cobranza = response.data.cobranza || response.data;
            }

            // Cerramos el modal de captura
            addCobranzaModal.value = false;
        }
    } catch (error) {
        console.error("Error al guardar la información de cobranza:", error);
        
        // Manejo de errores dinámico (por si el RFC ya existe u otra validación del servidor)
        const mensajeError = error.response?.data?.message || 'Ocurrió un error al intentar guardar los datos.';
        Swal.fire({
            icon: 'error',
            title: 'Error al guardar',
            text: mensajeError
        });
    } finally {
        savingCobranza.value = false;
    }
};

const abrirDetalleCobranza = (cobranza) => {
    selectedCobranza.value = cobranza;
    showCobranzaModal.value = true;
};

const fetchVisitaDetail = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get(`/visitas/${route.params.id}`);
        visita.value = response.data;
        
        if (visita.value.cliente_id || visita.value.nombre_plantel) {
            fetchFullHistory(visita.value.cliente_id);
        }
    } catch (err) {
        error.value = "Expediente no localizado en el servidor central.";
    } finally {
        loading.value = false;
    }
};

const fetchFullHistory = async (clienteId) => {
    loadingHistory.value = true;
    try {
        const response = await axios.get('/visitas', { 
            params: { 
                cliente_id: clienteId, 
                full_history: 1, 
                include_logs: 1 
            } 
        });
        
        const dataReceived = response.data.data || response.data;
        historial.value = Array.isArray(dataReceived) 
            ? dataReceived.sort((a,b) => a.id - b.id)
            : [];
            
    } catch (e) {
        console.error("Fallo al sincronizar historial:", e);
    } finally {
        loadingHistory.value = false;
    }
};

/**
 * DETERMINA EL ESTADO FINAL REAL:
 * Si hay historial, tomamos el resultado de la última visita (el ID más alto).
 * Si no hay historial extra, usamos el resultado de la visita base.
 */
const ultimoResultado = computed(() => {
    if (!historial.value.length) return visita.value?.resultado_visita || 'seguimiento';
    const sorted = [...historial.value].sort((a, b) => b.id - a.id);
    return sorted[0].resultado_visita;
});

const allLogs = computed(() => {
    const aggregated = [];
    historial.value.forEach(v => {
        if (v.logs && v.logs.length) {
            v.logs.forEach(l => {
                aggregated.push({
                    ...l,
                    visit_type: v.es_primera_visita ? 'primera' : 'seguimiento',
                    visit_date: v.fecha
                });
            });
        }
    });
    return aggregated.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const proximoCompromisoFinal = computed(() => {
    if (!visita.value) return null;
    const conAgenda = [...historial.value]
        .filter(h => h.proxima_visita_estimada)
        .sort((a, b) => b.id - a.id);

    if (conAgenda.length > 0) return { fecha: conAgenda[0].proxima_visita_estimada, accion: conAgenda[0].proxima_accion };
    return null;
});

const toggleExpand = (id) => expandedId.value = expandedId.value === id ? null : id;

const parseMateriales = (raw) => {
    if (!raw) return { interes: [], entregado: [] };
    if (typeof raw === 'object' && !Array.isArray(raw)) return raw;
    try { return JSON.parse(raw); } catch (e) { return { interes: [], entregado: [] }; }
};

const formatLevels = (levels) => {
    if (!levels) return ['General'];
    if (Array.isArray(levels)) return levels;
    return levels.split(',').map(l => l.trim()).filter(l => l.length > 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '---';
    const cleanDate = dateString.split('T')[0];
    const [year, month, day] = cleanDate.split('-').map(Number);
    return new Date(year, month - 1, day).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
};

const formatDateOnly = (dateString) => {
    if (!dateString) return '---';
    return new Date(dateString).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatDateShort = (dateString) => {
    if (!dateString) return '---';
    return new Date(dateString).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: '2-digit' });
};

const formatTimeOnly = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
};

const getOutcomeClass = (outcome) => {
    const base = 'status-badge uppercase font-black tracking-widest ';
    if (outcome === 'compra') return base + 'bg-green-100 text-green-700 border-green-200';
    if (outcome === 'rechazo') return base + 'bg-red-100 text-red-700 border-red-200';
    return base + 'bg-orange-100 text-orange-700 border-orange-200';
};

onMounted(fetchVisitaDetail);
</script>

<style scoped>
.info-card { background: white; padding: 40px; border-radius: 40px; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #000000; margin-bottom: 30px; border-bottom: 2px solid #f8fafc; padding-bottom: 15px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 2px; }

.label-large { display: block; font-size: 0.72rem; font-weight: 900; text-transform: uppercase; color: #000000; margin-bottom: 6px; letter-spacing: 0.12em; opacity: 0.8; }
.value-text { color: #be5e5e; line-height: 1.4;  }

.status-badge { padding: 6px 16px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; display: inline-block; border: 1px solid transparent; }
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0, 0, 0, 0.08); }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

.table-responsive { width: 100%; background: white; transition: all 0.3s ease; }
.table-shadow-lg { box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); }

.table-cell { padding: 16px 20px; vertical-align: middle; }

.btn-primary-action { background: linear-gradient(135deg, #a93339 0%, #881337 100%); color: white; padding: 14px 45px; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; display: flex; align-items: center; justify-content: center; gap: 10px; }

.btn-primary { background: linear-gradient(135deg, #e4989c 0%, #d8afbb 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
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

.table-header { padding: 14px 16px; font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; text-align: left; }
.table-shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02); }

.badge-red-outline { @apply bg-red-50 text-red-700 text-[9px] font-black uppercase px-2 py-0.5 rounded-lg border border-red-100; }
.table-header { padding: 14px 16px; font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; text-align: left; }
.table-cell { padding: 16px; font-size: 0.9rem; }

@media (max-width: 768px) {
    .responsive-table { display: block; border: none; }
    .responsive-table thead { display: none; } 
    .responsive-table tbody { display: block; }
    .responsive-table tr { 
        display: block; 
        margin-bottom: 1.5rem; 
        border: 1px solid #f1f5f9; 
        border-radius: 20px; 
        padding: 20px;
        background: white;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .responsive-table td { 
        display: flex; 
        flex-direction: column; 
        padding: 10px 0; 
        border: none;
        text-align: left !important;
    }
    /* Inyecta el título dinámico desde el atributo data-label */
    .responsive-table td::before {
        content: attr(data-label);
        font-size: 8px;
        font-weight: 900;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 4px;
        letter-spacing: 0.15em;
    }
}

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* ==========================================================================
   ESTILOS DE CAPA Y CORRECCIÓN DE AMPLIACIÓN PARA EL MODAL DE COBRANZA
   ========================================================================== */
.custom-modal-backdrop {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background-color: rgba(15, 23, 42, 0.4) !important;
    backdrop-filter: blur(8px) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    z-index: 99999 !important;
    padding: 24px;
}

.custom-modal-window {
    width: 100% !important;
    max-width: 32rem !important;
    border-radius: 2.5rem !important;
    overflow: hidden !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15) !important;
    animation: modalBounceIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
}

@keyframes modalBounceIn {
    from { opacity: 0; transform: scale(0.93) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

@media (max-width: 640px) {
    .custom-modal-backdrop { padding: 12px; }
    .custom-modal-window { border-radius: 2rem !important; }
}

/* ==========================================================================
   ESTILOS DE CAPA, CONTENEDOR SÓLIDO BLANCO Y PADDINGS DEL MODAL
   ========================================================================== */
.custom-modal-backdrop {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background-color: rgba(15, 23, 42, 0.4) !important;
    backdrop-filter: blur(8px) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    z-index: 99999 !important;
    padding: 24px !important;
}

.custom-modal-window {
    width: 100% !important;
    max-width: 32rem !important;
    background-color: #ffffff !important;
    border-radius: 2.5rem !important;
    overflow: hidden !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
    box-sizing: border-box !important;
    animation: modalBounceIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
}

/* Espaciado interno simétrico hacia las cuatro orillas */
.custom-modal-content {
    padding-top: 2rem !important;
    padding-bottom: 2rem !important;
    padding-left: 2rem !important;
    padding-right: 2rem !important;
    box-sizing: border-box !important;
}

/* Forzar la alineación de botones uno al lado del otro */
.custom-modal-buttons {
    display: flex !important;
    flex-direction: row !important;
    gap: 16px !important;
    width: 100% !important;
}

.modal-btn {
    flex: 1 !important;
    padding-top: 12px !important;
    padding-bottom: 12px !important;
}

@keyframes modalBounceIn {
    from { opacity: 0; transform: scale(0.93) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

@media (max-width: 640px) {
    .custom-modal-backdrop { padding: 16px !important; }
    .custom-modal-window { border-radius: 2rem !important; }
    .custom-modal-content { padding: 1.25rem !important; }
    .custom-modal-buttons { gap: 10px !important; }
}
</style>