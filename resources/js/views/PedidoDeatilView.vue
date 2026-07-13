<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <div class="header-info min-w-0">
                    <h1 class="text-2xl md:text-3xl font-black text-slate-800 tracking-tight leading-tight break-words uppercase">
                        Número de Pedido #{{ pedido && (pedido.numero_referencia || pedido.display_id) ? (pedido.numero_referencia || pedido.display_id) : id }}
                    </h1>
                    <p class="text-xs md:text-sm text-red-600 font-bold mt-1 uppercase tracking-widest italic">Gestión logística y auditoría de materiales.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <button 
                        v-if="pedido && pedido.status === 'PENDIENTE'"
                        @click="router.push({ name: 'PedidoEdit', params: { id: pedido.id } })" 
                        class="btn-primary shadow-sm shrink-0 w-full sm:w-auto uppercase flex items-center justify-center gap-2"
                    >
                        <i class="fas fa-edit"></i> Editar Pedido
                    </button>

                    <button @click="router.push('/pedidos')" class="btn-secondary shadow-sm shrink-0 w-full sm:w-auto flex items-center justify-center gap-2">
                        <i class="fas fa-arrow-left"></i> Volver al Historial
                    </button>
                </div>
            </div>
            
            <!-- Loader -->
            <div v-if="loading" class="loading-state py-20 text-center animate-pulse">
                <i class="fas fa-circle-notch fa-spin text-4xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]">Sincronizando con base de datos central...</p>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="error-message-container p-6 md:p-10 text-center bg-red-50 border-2 border-red-100 rounded-[2.5rem] shadow-sm animate-fade-in mx-2">
                <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle fa-2xl"></i>
                </div>
                <h2 class="text-xl font-black text-red-800 uppercase tracking-tighter">Error de Acceso</h2>
                <p class="text-red-600/70 text-sm mt-2 font-medium uppercase">{{ error }}</p>
                <button @click="fetchPedidoDetail" class="btn-primary mt-6 px-10 py-3 rounded-2xl shadow-lg bg-black text-white uppercase font-black text-xs tracking-widest">Reintentar Conexión</button>
            </div>

            <div v-else-if="pedido" class="space-y-6 md:space-y-8 animate-fade-in px-1 md:px-0 overflow-hidden">
                
                <!-- 1. GRID DE INFORMACIÓN GENERAL -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <!-- Bloque: Cliente Maestro -->
                    <div class="info-card shadow-premium border-t-4 border-t-red-700 bg-white p-6 rounded-3xl border border-slate-100">
                        <div class="section-title !mb-6">
                            <i class="fas fa-user-tag text-red-700"></i> 1. Información del Cliente
                        </div>
                        <div class="space-y-4">
                            <div class="pb-3 border-b border-slate-50">
                                <label class="label-mini label-large text-slate-400">PLANTEL / DISTRIBUIDOR</label>
                                <p class="text-sm font-black text-slate-800 value-text uppercase leading-tight">
                                    {{ pedido.cliente?.name || 'No disponible' }}
                                </p>
                            </div>
                            <div class="pt-2">
                                <label class="label-mini label-large text-slate-400">Prioridad</label>
                                <span :class="getPriorityClass(pedido.prioridad)" class="status-badge !px-3 !py-1 text-[10px]">
                                    {{ (pedido.prioridad || 'media').toUpperCase() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Bloque: Logística y Destino -->
                    <div class="info-card shadow-premium border-t-4 border-t-slate-800 bg-white p-6 rounded-3xl border border-slate-100">
                        <div class="section-title !mb-6">
                            <i class="fas fa-truck text-slate-800"></i> 2 . Recepción y Logística de Envío
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="label-mini text-red-800 label-large font-bold uppercase">NOMBRE DEL DESTINATARIO</label>
                                <p class="text-base font-black value-text text-slate-900 uppercase break-words leading-tight">
                                    {{ pedido.receiver_type === 'nuevo' ? (pedido.receptor?.nombre || pedido.receiver_nombre || 'S/D') : (pedido.cliente?.contacto || 'TITULAR DE CUENTA') }}
                                </p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 pt-2">
                                <div>
                                    <label class="label-mini label-large">RFC</label>
                                    <p class="text-[10px] font-mono font-black text-slate-700 value-text uppercase tracking-tighter truncate">
                                        {{ pedido.receiver_type === 'nuevo' ? (pedido.receptor?.rfc || pedido.receiver_rfc || 'N/A') : (pedido.cliente?.rfc || 'N/A') }}
                                    </p>
                                </div>
                                <div>
                                    <label class="label-mini label-large text-slate-400">Régimen Fiscal</label>
                                    <p class="text-[10px] font-black text-red-600 value-text uppercase leading-tight truncate">
                                        {{ pedido.receiver_type === 'nuevo' ? (pedido.receptor?.receiver_regimen_fiscal || pedido.receiver_regimen_fiscal || 'SIN DATO') : (pedido.cliente?.regimen_fiscal || 'SIN DATO') }}
                                    </p>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-slate-50 min-w-0">
                                <label class="label-mini label-large text-slate-400">Correo Electrónico</label>
                                <p class="text-xs font-bold text-slate-800 value-text truncate lowercase" style="text-transform: none !important;">
                                    <i class="fas fa-envelope mr-1 text-red-300"></i>
                                    {{ pedido.receiver_type === 'nuevo' ? (pedido.receptor?.correo || pedido.receiver_correo || '---') : (pedido.cliente?.email || '---') }}
                                </p>
                                <label class="label-mini label-large text-slate-400">Telefono</label>
                                <p class="text-xs font-bold text-slate-800 value-text mt-1 uppercase">
                                    <i class="fas fa-phone mr-1 text-red-300"></i>
                                    {{ pedido.receiver_type === 'nuevo' ? (pedido.receptor?.telefono || pedido.receiver_telefono || 'N/A') : (pedido.cliente?.telefono || 'N/A') }}
                                </p>
                            </div>

                            <div>
                                <label class="label-mini label-large text-slate-400 uppercase">DIRECCIÓN COMPLETA</label>
                                <div class="text-[10px] text-slate-600 leading-relaxed font-medium italic bg-slate-50 p-3 rounded-xl border border-slate-100 break-words uppercase">
                                    <i class="fas value-text fa-map-marker-alt text-red-500 mr-1"></i> 
                                    {{ formatFullAddress(pedido) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bloque: Resumen de Envío -->
                    <div class="info-card shadow-premium border-t-4 border-t-red-700 bg-white p-6 rounded-3xl border border-slate-100">
                        <div class="section-title !mb-6">
                            <i class="fas fa-box-open text-red-700"></i> 3. Estatus de envio
                        </div>
                        <div class="space-y-6">
                            <div class="bg-red-50/30 p-4 rounded-2xl border border-red-100">
                                    <label class="label-mini label-large">Método de Envío</label>
                                    <span class="text-xs font-black text-red-700 value-text uppercase block">{{ getDeliveryOption(pedido.delivery_option) }}</span>
                                </div>
                            <div class="grid grid-cols-1 gap-4 mb-5">
                                <div v-if="pedido.delivery_option === 'paqueteria'" class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                    <label class="label-mini label-large">Paquetería</label>
                                    <span class="text-xs font-black text-slate-800 value-text uppercase block">{{ pedido.paqueteria_nombre || 'POR DEFINIR' }}</span>
                                </div>

                                <div v-else-if="pedido.commentary_delivery_option" class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                    <label class="label-mini label-large">Instrucciones de logistica</label>
                                    <span class="text-[10px] font-bold text-slate-500 value-text uppercase leading-tight block">
                                        {{ pedido.commentary_delivery_option }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="label-mini label-large">Estado del Pedido</label>
                                <span :class="getStatusClass(pedido.status)" class="status-badge w-full text-center value-text py-2.5 shadow-sm rounded-xl">
                                    {{ pedido.status }}
                                </span>
                                <button 
                                    v-if="user && user.role === 'admin'" 
                                    type="button"
                                    @click="openStatusModal"
                                    class="btn-secondary flex items-center justify-center gap-2 w-full mt-2 !border-slate-300 hover:bg-slate-50 transition-colors py-2.5"
                                >
                                    <i class="fas fa-edit text-amber-600"></i>
                                    ACTUALIZAR ESTADO
                                </button>
                            </div>
                            <div v-if="pedido && pedido.historial_status && pedido.historial_status.length > 0" class="table-container animate-fade-in p-0 mt-6">
                                <label class="label-mini label-large">Historial de Cambios de Estado</label>
                                <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm bg-white">
                                    <table class="min-width-full divide-y divide-gray-200 responsive-table">
                                        <thead class="bg-gray-100 hidden md:table-header-group">
                                            <tr>
                                                <th class="table-header">Representante</th>
                                                <th class="table-header text-center w-36">Nuevo Estado</th>
                                                <th class="table-header text-left">Comentarios</th>
                                                <th class="table-header text-right w-40">Fecha de Modificación</th>
                                            </tr>
                                        </thead>

                                        <tbody class="bg-white divide-y divide-gray-100 block md:table-row-group">
                                            <tr v-for="historial in pedido.historial_status" :key="historial.id" 
                                                class="hover:bg-gray-50 transition-colors block md:table-row relative p-5 md:p-0 border-b md:border-none">
                                                
                                                <td class="table-cell block md:table-cell" data-label="REPRESENTANTE">
                                                    <p class="font-black text-slate-800 text-xs uppercase leading-tight">
                                                        {{ historial.user.full_name }}
                                                    </p>
                                                </td>

                                                <td class="table-cell text-left md:text-center block md:table-cell" data-label="ESTADO">
                                                    <span :class="getStatusClass(historial.status)" class="status-badge inline-block text-center text-[10px] py-1 px-3 rounded-lg font-black tracking-wider shadow-sm uppercase">
                                                        {{ historial.status }}
                                                    </span>
                                                </td>

                                                <td class="table-cell text-left font-bold text-slate-600 text-xs block md:table-cell whitespace-pre-line" data-label="COMENTARIOS">
                                                    {{ historial.comentarios }}
                                                </td>

                                                <td class="table-cell text-left md:text-right font-bold text-slate-400 text-xs block md:table-cell" data-label="FECHA">
                                                    {{ new Date(historial.created_at).toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' }) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

               

                <!-- 3. DESGLOSE DE MATERIALES -->
                <div class="mt-8">
                    <div class="info-card !p-0 shadow-premium border border-slate-200 rounded-[2.5rem] bg-white overflow-hidden border-slate-100">
                        
                         <div class="section-title !mb-6 p-8 pb-0">
                            <i class="fas fa-box-open text-red-700"></i> 4. Material
                        </div>
                        <!-- 2. TABLA: DETALLE DE PEDIDO (RESPONSIVA) -->
<div class="info-card shadow-premium !p-0 overflow-hidden border border-slate-200 rounded-[2.5rem] bg-white animate-fade-in">
    <div class="p-6 md:p-8 border-b border-slate-50 flex items-center gap-3">
        <div class="w-10 h-10 bg-red-50 text-red-600 rounded-xl flex items-center justify-center shadow-sm">
            <i class="fas fa-box-open text-lg"></i>
        </div>
        <h2 class="text-xl label-large font-black text-slate-800 uppercase tracking-tight">Detalle de Materiales del Pedido</h2>
    </div>

    <div class="table-container animate-fade-in p-4 md:p-8 pt-0">
        <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm bg-white mt-6">
            <table class="min-width-full divide-y divide-gray-200 responsive-table">
                <thead class="bg-gray-100 hidden md:table-header-group">
                    <tr>
                        <th class="table-header">Libro</th>
                        <th class="table-header text-center w-32">Tipo</th>
                        <th class="table-header text-center w-32">Formato</th>
                        <th class="table-header text-center w-24">Cantidad</th>
                        <th class="table-header text-right w-32">Precio Unitario</th>
                        <th class="table-header text-right w-32">Subtotal</th>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-100 block md:table-row-group">
                    <tr v-for="detalle in pedido.detalles" :key="detalle.id" 
                        class="hover:bg-gray-50 transition-colors block md:table-row relative p-5 md:p-0 border-b md:border-none">
                        
                        <td class="table-cell block md:table-cell" data-label="LIBRO">
                            <p class="font-black text-slate-800 text-sm uppercase leading-tight">
                                {{ detalle.libro?.titulo || 'Material no identificado' }}
                            </p>
                            <div class="mt-1.5 flex items-center gap-2">
                                <span class="text-[9px] font-black text-red-700 bg-red-50 px-2 py-0.5 rounded border border-red-100 uppercase tracking-widest">
                                    ISBN: {{ detalle.libro?.ISBN || 'N/A' }}
                                </span>
                            </div>
                        </td>

                        <td class="table-cell text-left md:text-center block md:table-cell" data-label="TIPO">
                            <span class="status-badge bg-slate-100 text-slate-600 border border-slate-200">
                                {{ (detalle.tipo_licencia || 'N/A').toUpperCase() }}
                            </span>
                        </td>

                        <td class="table-cell text-left md:text-center block md:table-cell" data-label="FORMATO">
                            <span class="status-badge bg-blue-50 text-blue-700 border border-blue-100">
                                {{ (detalle.tipo || 'VENTA').toUpperCase() }}
                            </span>
                        </td>

                        <td class="table-cell text-left md:text-center font-black text-slate-700 text-lg block md:table-cell" data-label="CANTIDAD">
                            {{ detalle.cantidad }}
                        </td>

                        <td class="table-cell text-left md:text-right font-bold text-slate-500 text-xs block md:table-cell" data-label="P. UNITARIO">
                            {{ formatCurrency(detalle.precio_unitario) }}
                        </td>

                        <td class="table-cell text-left md:text-right font-black text-red-800 text-base tracking-tighter block md:table-cell" data-label="SUBTOTAL">
                            {{ formatCurrency(detalle.costo_total) }}
                        </td>
                    </tr>
                </tbody>

                <tfoot class="bg-slate-50 border-t-2 border-slate-100 block md:table-footer-group">
                    <!-- Fila de Unidades Totales -->
                    <tr class="border-b border-slate-100/50 flex flex-col md:table-row p-4 md:p-0">
                        <td colspan="3" class="hidden md:table-cell px-8 py-4 text-right font-black uppercase text-[10px] tracking-[0.2em] text-slate-400">
                            Volumen Total de Unidades:
                        </td>
                        <td class="table-cell block md:table-cell text-center md:px-4 md:py-4 font-black text-xl text-slate-700">
                            <span class="md:hidden block text-[9px] font-black text-slate-400 uppercase mb-1 tracking-widest">Total Unidades</span>
                            {{ totalUnidades }}
                        </td>
                        <td colspan="2" class="hidden md:table-cell bg-slate-50/30"></td>
                    </tr>

                    <!-- Fila de Monto Total -->
                    <tr class="flex flex-col md:table-row p-4 md:p-0">
                        <td colspan="5" class="hidden md:table-cell px-8 py-6 text-right font-black uppercase text-[10px] tracking-[0.2em] text-slate-400">
                            Inversión Total del Expediente:
                        </td>
                        <td class="table-cell block md:table-cell text-center md:text-right md:px-6 md:py-6 font-black text-3xl md:text-2xl text-red-700 leading-none tracking-tighter">
                            <span class="md:hidden block text-[9px] font-black text-slate-400 uppercase mb-2 tracking-widest">Inversión Total</span>
                            {{ formatCurrency(totalOrderCostNum) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
                    </div>
                </div>
                 <!-- 4. COMENTARIOS GENERALES -->
                <div v-if="pedido.comments" class="info-card comments-section bg-white p-8 md:p-10 rounded-[3rem] border-2 border-amber-200 shadow-premium relative overflow-hidden animate-fade-in mx-2">
                    <div class="absolute -right-6 -top-6 w-32 h-32 bg-amber-50 rounded-full flex items-center justify-center opacity-40">
                        <i class="fas fa-quote-right text-6xl text-amber-200 rotate-12"></i>
                    </div>
                    
                    <div class="relative z-10">
                        <span class="inline-flex  wh">
                            <i class="fas fa-comment-dots "></i> 5. Comentarios generales del pedido
                        </span>
                        <div class="bg-amber-50/50 p-6 rounded-[2rem] border border-dashed border-amber-200">
                            <p class="text-slate-800 text-base md:text-lg font-bold italic leading-relaxed whitespace-pre-wrap">
                                "{{ pedido.comments }}"
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 6. EXPEDIENTE DIGITAL -->
                <div class="info-card shadow-premium border-l-8 border-l-slate-800 bg-white p-6 rounded-3xl overflow-hidden border border-slate-100">
                    
                    <div class="section-title !mb-0 border-none">
                        <i class="fas fa-history text-red-700"></i> 6. Expediente Digital y Documentos
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                        <div class="flex items-center justify-between p-5 rounded-2xl border-2 transition-all" 
                             :class="pedido.factura_path ? 'border-red-50 bg-red-50/20' : 'border-slate-50 bg-slate-50/30 opacity-60'">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white shadow-sm shrink-0">
                                    <i class="fas fa-file-invoice text-xl" :class="pedido.factura_path ? 'text-red-600' : 'text-slate-300'"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest label-large">Factura PDF</p>
                                    <p class="text-xs font-bold value-text text-slate-700 truncate uppercase">{{ pedido.factura_path ? 'DESCARGA LISTA' : 'PENDIENTE' }}</p>
                                </div>
                            </div>
                            <a v-if="pedido.factura_path" :href="pedido.factura_url" target="_blank" class="btn-icon-action bg-red-600 shrink-0 shadow-lg shadow-red-100 hover:scale-110 transition-transform border-none cursor-pointer"><i class="fas fa-download"></i></a>
                        </div>

                        <div class="flex items-center justify-between p-5 rounded-2xl border-2 transition-all bg-slate-50/30 border-slate-100 shadow-sm">
                            <div class="flex items-center gap-3 min-w-0 w-full justify-between">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest label-large mb-5">Guía de Envío</p>
                                <div v-if="user && user.role === 'admin'">
                                    <div>
                                        <input 
                                            type="file" 
                                            id="guia-document-file-input" 
                                            @change="handleFileChange" 
                                            accept="image/jpeg,image/png,application/pdf" 
                                            style="display: none !important;"
                                            :disabled="uploadLoading"
                                        />

                                        <label v-if="!selectedFiles || selectedFiles.length === 0" 
                                            for="guia-document-file-input" 
                                            class="btn-primary px-4 py-2 text-center text-xs font-black uppercase tracking-widest cursor-pointer select-none inline-flex items-center justify-center">
                                            Adjuntar Guía
                                        </label>

                                        <button v-else 
                                                @click="uploadGuia" 
                                                :disabled="uploadLoading"
                                                class="btn-primary px-4 py-2 text-xs font-black uppercase tracking-widest cursor-pointer inline-flex items-center justify-center border-none">
                                            <i v-if="uploadLoading" class="fas fa-spinner fa-spin mr-1"></i>
                                            {{ uploadLoading ? 'Subiendo...' : 'Subir' }}
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white shadow-sm shrink-0">
                                            <i class="fas text-xl" :class="uploadLoading ? 'fa-spinner fa-spin text-red-600' : 'fa-truck text-slate-300'"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold value-text text-slate-700 truncate uppercase max-w-[110px] sm:max-w-[145px]">
                                                {{ selectedFiles && selectedFiles.length > 0 ? selectedFiles[0].name : '' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="pedido.guias && pedido.guias.length > 0" class="table-container mt-4 p-0">
                                    <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm bg-white">
                                        <table class="min-width-full divide-y divide-gray-200 responsive-table">
                                            <thead class="bg-gray-100 hidden md:table-header-group">
                                                <tr>
                                                    <th class="table-header">Documento</th>
                                                    <th class="table-header text-center w-32">Formato</th>
                                                    <th class="table-header text-center w-32">Peso</th>
                                                </tr>
                                            </thead>

                                            <tbody class="bg-white divide-y divide-gray-100 block md:table-row-group">
                                                <tr v-for="guia in pedido.guias" :key="guia.id" 
                                                    class="hover:bg-gray-50 transition-colors block md:table-row relative p-5 md:p-0 border-b md:border-none">
                                                    
                                                    <td class="table-cell block md:table-cell" data-label="DOCUMENTO">
                                                        <a :href="getViewableUrl(guia.public_url)" 
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="btn-note !bg-white hover:!border-red-600 hover:!text-red-600 flex items-center gap-2 w-full justify-center md:justify-start">
                                                                VER GUÍA
                                                        </a>
                                                    </td>

                                                    <td class="table-cell text-left md:text-center block md:table-cell" data-label="FORMATO">
                                                        <span :class="guia.extension === 'pdf' ? 'bg-red-50 text-red-700 border-red-100' : 'bg-blue-50 text-blue-700 border-blue-100'" 
                                                            class="status-badge border">
                                                            {{ (guia.extension || 'N/A').toUpperCase() }}
                                                        </span>
                                                    </td>

                                                    <td class="table-cell text-left md:text-center font-bold text-slate-500 text-xs block md:table-cell" data-label="PESO">
                                                        {{ guia.size }} KB
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            

                <!-- 5. HISTORIAL DE AJUSTES (MOSTRAR SOLO SI HAY LOGS) -->
                <div v-if="pedido.logs && pedido.logs.length > 0" class="info-card shadow-premium border-t-8 border-t-slate-800 bg-white p-0 rounded-[2.5rem] border border-slate-100 overflow-hidden">
                    <div class="p-8 border-b border-slate-50 flex items-center justify-between bg-white">
                        
                         <div class="section-title !mb-0 border-none">
                            <i class="fas fa-history text-red-700"></i> 7. MODIFICACIONES
                        </div>
                    </div>

                    <div class="table-container mt-4 animate-fade-in p-8 pt-0">
                         <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
        <label class="label-mini label-large mb-6 text-slate-800 font-black tracking-tighter uppercase">
            <i class="fas fa-history mr-1 text-red-700"></i> 5. Bitácora de Ajustes Técnicos
        </label>

        <div class="table-responsive table-shadow-lg border rounded-xl overflow-hidden shadow-sm bg-white">
            <table class="min-width-full divide-y divide-gray-200 responsive-table">
                <thead class="bg-gray-100 hidden md:table-header-group">
                    <tr>
                        <th class="table-header text-center w-24">N</th>
                        <th class="table-header">Motivo de la Modificación</th>
                        <th class="table-header w-56">Responsable</th>
                        <th class="table-header text-right w-48">Sincronización</th>
                    </tr>
                </thead>
                
                <tbody class="bg-white divide-y divide-gray-100 block md:table-row-group">
                    <tr v-for="(log, index) in pedido.logs" :key="log.id" 
                        class="hover:bg-gray-50 transition-colors block md:table-row relative p-5 md:p-0 border-b md:border-none">
                        
                        <td class="table-cell block md:table-cell text-left md:text-center" data-label="N">
                            <div class="flex justify-start md:justify-center">
                                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-[10px] font-black border-2" 
                                    :class="index === 0 ? 'bg-red-50 border-red-100 text-red-600' : 'bg-slate-50 border-slate-100 text-slate-400'">
                                    {{ pedido.logs.length - index }}
                                </span>
                            </div>
                        </td>

                        <td class="table-cell block md:table-cell" data-label="MOTIVO">
                            <p class="text-[11px] font-bold text-slate-700 italic leading-relaxed uppercase">
                                "{{ log.motivo_cambio || 'Sin justificación técnica' }}"
                            </p>
                        </td>

                        <td class="table-cell block md:table-cell" data-label="RESPONSABLE">
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-black text-slate-800 uppercase tracking-tight">
                                    {{ log.user?.name || 'Sistema' }}
                                </span>
                            </div>
                        </td>

                        <td class="table-cell block md:table-cell text-left md:text-right" data-label="SINCRONIZACIÓN">
                            <div class="flex flex-col items-start md:items-end">
                                <span class="text-[11px] font-black text-slate-800 uppercase leading-none">
                                    {{ formatDateOnly(log.created_at) }}
                                </span>
                                <span class="text-[9px] font-bold text-slate-400 mt-1 uppercase italic">
                                    {{ formatTimeOnly(log.created_at) }}
                                </span>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!pedido.logs || pedido.logs.length === 0" class="block md:table-row">
                        <td colspan="4" class="px-6 py-10 text-center italic text-slate-300 font-black text-[10px] uppercase tracking-widest block md:table-cell">
                            No se han registrado modificaciones en este expediente
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
                         </div>
                    </div>
                </div>

            </div>
        </div>
        <div v-if="showStatusModal" class="custom-modal-backdrop">
            <div class="custom-modal-window bg-white border-t-8 border-t-red-700 shadow-premium">
                
                <div class="custom-modal-content">
                    
                    <div class="flex justify-between items-center bg-white pb-4 mb-6 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-7 bg-red-700 rounded-full"></div>
                            <h3 class="text-sm md:text-base font-black text-black uppercase tracking-wider m-0">
                                <i class="fas fa-tasks text-red-700 mr-1"></i> Cambiar Estado del Pedido
                            </h3>
                        </div>
                    </div>

                    <form @submit.prevent="submitStatusUpdate" class="space-y-6 m-0 bg-white">
                        <div class="form-group">
                            <label class="label-large mb-2 block">Elegir Estado *</label>
                            <select 
                                v-model="statusForm.status" 
                                required 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-black uppercase tracking-wider text-slate-700 focus:outline-none focus:border-red-600"
                            >
                                <option value="EN PROCESO">EN PROCESO</option>
                                <option value="SURTIDO">SURTIDO</option>
                                <option value="ENVIADO">ENVIADO</option>
                                <option value="ENTREGADO">ENTREGADO</option>
                                <option value="CANCELADO">CANCELADO</option>
                                <option value="DEMORADO">DEMORADO</option>
                                <option value="PROBLEMAS DE ENVÍO">PROBLEMAS DE ENVÍO</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="label-large mb-2 block">Comentarios / Justificación *</label>
                            <textarea 
                                v-model="statusForm.comentarios" 
                                required 
                                rows="4"
                                placeholder="Escriba los motivos o comentarios del cambio de estado..."
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs font-bold uppercase text-slate-800 focus:outline-none focus:border-red-600 resize-none"
                            ></textarea>
                        </div>

                        <div class="custom-modal-buttons pt-5 border-t border-slate-100 flex justify-end gap-3">
                            <button type="button" @click="closeStatusModal" class="btn-secondary modal-btn text-xs font-black uppercase tracking-widest text-center">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="savingStatus" class="btn-primary px-5 py-2.5 modal-btn text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2">
                                <i v-if="savingStatus" class="fas fa-circle-notch fa-spin"></i>
                                <i v-else class="fas fa-save"></i>
                                {{ savingStatus ? 'GUARDANDO...' : 'GUARDAR' }}
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../axios';

const route = useRoute();
const router = useRouter();
const id = route.params.id; 
const pedido = ref(null);
const loading = ref(false);
const error = ref(null);
const user = ref(null)

// ── GESTIÓN DE GUÍAS EN EXPEDIENTE DIGITAL ──
const uploadLoading = ref(false)
const selectedFiles = ref([])

// ── GESTIÓN DEL MODAL DE CAMBIO DE ESTATUS ──
const showStatusModal = ref(false)
const savingStatus = ref(false)
const statusForm = ref({
    status: '',
    comentarios: ''
})

// Abre el modal inicializando los valores con el estatus actual del pedido
const openStatusModal = () => {
    // Si el estatus es 'PROCESO' o 'EN PROCESO', aseguramos que haga match con los valores del select
    const currentStatus = pedido.value?.status === 'EN PROCESO' ? 'PROCESO' : (pedido.value?.status || 'PROCESO')
    statusForm.value = {
        status: currentStatus,
        comentarios: ''
    }
    showStatusModal.value = true
}

// Cierra y limpia el formulario
const closeStatusModal = () => {
    showStatusModal.value = false
    statusForm.value = { status: '', comentarios: '' }
}

// Envía la petición asíncrona a Laravel
const submitStatusUpdate = async () => {
    if (!statusForm.value.status || !statusForm.value.comentarios) return

    savingStatus.value = true
    try {
        const response = await axios.post(`/pedidos/${id}/update-status`, {
            status: statusForm.value.status,
            comentarios: statusForm.value.comentarios
        })

        if (pedido.value) {
            pedido.value.status = response.data.status || statusForm.value.status
            
            // Inicializa el arreglo en Vue si no viniera definido desde el servidor
            if (!pedido.value.historial_status) {
                pedido.value.historial_status = []
            }
            
            // Inserta el nuevo registro al principio de la tabla reactiva con los datos locales
            pedido.value.historial_status.unshift({
                id: response.data.log?.id || Date.now(),
                user_id: user.value?.id,
                user: { full_name: response.data.log.user.full_name },
                status: response.data.status || statusForm.value.status,
                comentarios: statusForm.value.comentarios,
                created_at: new Date().toISOString()
            })
        }

        alert('Estado del pedido actualizado correctamente.')
        closeStatusModal()
    } catch (err) {
        alert(err.response?.data?.message || 'Error al actualizar el estado del expediente.')
    } finally {
        savingStatus.value = false
    }
}

// Función que maneja la selección del archivo
const handleFileChange = (event) => {
  selectedFiles.value = Array.from(event.target.files)
}

// Función que envía los archivos al endpoint de Laravel
const uploadGuia = async () => {
  if (selectedFiles.value.length === 0) return

  uploadLoading.value = true
  const formData = new FormData()
  formData.append('pedido_id', pedido.value.id)
  
  selectedFiles.value.forEach((file) => {
    formData.append('files[]', file)
  })

  try {
    const response = await axios.post('pedidos/store-guia', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    // Si tu consulta inicial mapea las guías en el objeto pedido, las inyectamos dinámicamente
    if (!pedido.value.guias) pedido.value.guias = []
    pedido.value.guias.push(...response.data.guias)
    
    selectedFiles.value = []
    // Si manejas una función de alerta o toast en esta vista, la puedes disparar aquí
    alert('Archivo cargado exitosamente.')
  } catch (error) {
    alert(error.response?.data?.message || 'Error al subir el documento a Dropbox.')
  } finally {
    uploadLoading.value = false
  }
}

const getViewableUrl = (url) => {
    if (!url) return '#';
    return url.replace('dl=1', 'dl=0');
};

const fetchPedidoDetail = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get(`/pedidos/${id}`);
        pedido.value = response.data.pedido
        user.value = response.data.user
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudieron cargar los detalles técnicos del pedido.';
    } finally {
        loading.value = false;
    }
};
const totalUnidades = computed(() => {
    if (!pedido.value?.detalles) return 0;
    return pedido.value.detalles.reduce((acc, item) => acc + (Number(item.cantidad) || 0), 0);
});


const totalOrderCostNum = computed(() => {
    if (!pedido.value || !pedido.value.detalles) return 0;
    return pedido.value.detalles.reduce((sum, d) => sum + (parseFloat(d.costo_total) || 0), 0);
});

const formatCurrency = (value) => {
    return Number(value || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
};

const getStatusClass = (status) => {
    const base = 'status-badge ';
    switch (status) {
        case 'ENTREGADO': return base + 'bg-green-100 text-green-700 border border-green-200';
        case 'PENDIENTE': return base + 'bg-yellow-100 text-yellow-700 border border-yellow-200';
        case 'REVISIÓN': return base + 'bg-orange-100 text-orange-700 border border-orange-200';
        case 'PROCESO': return base + 'bg-blue-100 text-blue-700 border border-blue-200';
        case 'CANCELADO': return base + 'bg-red-100 text-red-700 border border-red-200';
        default: return base + 'bg-slate-100 text-slate-400';
    }
};

const getPriorityClass = (priority) => {
    if (!priority) return 'bg-slate-100 text-slate-600';
    switch (priority.toLowerCase()) {
        case 'alta': return 'bg-red-600 text-white font-black px-3 shadow-sm rounded-lg text-[10px] uppercase';
        case 'media': return 'bg-orange-100 text-orange-700 font-bold border border-orange-200 rounded-lg text-[10px] uppercase';
        case 'baja': return 'bg-slate-100 text-slate-400 border border-slate-200 rounded-lg text-[10px] uppercase';
        default: return 'bg-slate-50 text-slate-300 rounded-lg text-[10px] uppercase';
    }
};

const formatFullAddress = (p) => {
    if (p.delivery_address) return p.delivery_address;
    if (p.receiver_type === 'nuevo' && (p.delivery_calle_num || p.receptor?.direccion)) {
        return p.delivery_calle_num 
            ? `${p.delivery_calle_num}, COL. ${p.delivery_colonia}, ${p.delivery_municipio}, CP ${p.delivery_cp}`
            : (p.receptor?.direccion || 'DIRECCIÓN NO ESPECIFICADA');
    }
    return p.cliente?.direccion || 'ENTREGA EN SUCURSAL / OFICINA';
};

const getDeliveryOption = (option) => {
    switch (option) {
        case 'recoleccion': return 'RECOLECCIÓN EN ALMACÉN';
        case 'paqueteria': return 'PAQUETERÍA';
        case 'entrega': return 'ENTREGA DIRECTA';
        default: return 'ENTREGA DIRECTA';
    }
};

const formatDateOnly = (dateString) => {
    if (!dateString) return '---';
    return new Date(dateString).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatTimeOnly = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
};

onMounted(() => {
    window.scrollTo(0, 0);
    fetchPedidoDetail();
});
</script>

<style scoped>
.info-card { background: white; padding: 25px; border-radius: 24px; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; color: #000000; margin-bottom: 25px; border-bottom: 2px solid #fee2e2; padding-bottom: 12px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 2px; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block tracking-[0.1em]; }
.status-badge { padding: 4px 14px; border-radius: 20px; font-size: 0.65rem; font-weight: 900; display: inline-block; text-transform: uppercase; }
.wh { display: block; font-size: 0.85rem; font-weight: 900; text-transform: uppercase; color: #000000; margin-bottom: 6px; letter-spacing: 0.12em; opacity: 0.8; }
.shadow-premium { box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05); }

.table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }

.badge-format-red {
    display: inline-block; font-size: 10px; font-weight: 900; color: #b91c1c; text-transform: uppercase; background-color: #ffffff; padding: 4px 12px; border-radius: 8px; border: 1px solid #fee2e2; letter-spacing: 0.05em;
}

.btn-icon-action { width: 34px; height: 34px; color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; transition: transform 0.2s; flex-shrink: 0; border: none; cursor: pointer; }
.btn-edit-action { @apply bg-slate-900 text-white py-2.5 px-6 rounded-xl text-sm font-black transition-all hover:bg-slate-700; border: none; cursor: pointer; }
.btn-secondary-custom { @apply bg-white border-2 border-slate-200 py-2.5 px-6 rounded-xl text-sm font-black transition-all hover:bg-slate-50 text-black; cursor: pointer; }

.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.btn-primary { background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.btn-secondary { padding: 8px 15px; background: white; border: 1px solid #cbd5e1; border-radius: 12px; color: #64748b; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; cursor:pointer; }

.table-header { padding: 14px 16px; font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; text-align: left; }
.table-cell { padding: 14px 16px; font-size: 0.85rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
.table-shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02); }

.value-text { color: #be5e5e; line-height: 1.4; }
.label-large { display: block; font-size: 0.72rem; font-weight: 900; text-transform: uppercase; color: #000000; margin-bottom: 6px; letter-spacing: 0.12em; opacity: 0.8; }
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