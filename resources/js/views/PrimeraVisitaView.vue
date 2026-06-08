<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-black uppercase tracking-tighter">Registro de Primera Visita</h1>
                    <p class="text-xs md:text-sm text-red-600 font-bold uppercase tracking-widest mt-1">Alta de prospecto: La captura de coordenadas GPS es obligatoria.</p>
                </div>
                <button @click="$router.push('/visitas')" class="btn-secondary shadow-sm shrink-0 w-full sm:w-auto">
                    <i class="fas fa-arrow-left"></i> Volver al Historial
                </button>
            </div>

            <form @submit.prevent="handleSubmit">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- BLOQUE 1: DATOS DEL PLANTEL -->
                    <div class="form-section shadow-premium border-t-8 border-t-red-700 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                        <div class="section-title label-large  text-black">
                            <i class="fas fa-school ext-red-700"></i>1. Datos del Plantel
                        </div>
                        
                        <!-- NOMBRE DEL PLANTEL -->
                        <div class="form-group mb-6 relative">
                            <label class="label-style">Nombre del Plantel *</label>
                            <div class="relative">
                                <input 
                                    v-model="form.plantel.name" 
                                    @blur="validateUniqueness('name')"
                                    type="text" 
                                    class="form-input font-bold" 
                                    :class="{'border-red-500 bg-red-50': fieldValidation.name.error}"
                                    placeholder="Nombre oficial de la institución" 
                                    required 
                                    minlength="5" 
                                    :disabled="loading"
                                >
                                <i v-if="validatingFields.name" class="fas fa-spinner fa-spin absolute right-4 top-1/2 -translate-y-1/2 text-red-600"></i>
                                <p v-if="fieldValidation.name.error" class="text-[9px] text-red-600 font-black mt-1 uppercase animate-pulse">
                                    <i class="fas fa-times-circle"></i> {{ fieldValidation.name.message }}
                                </p>
                            </div>
                        </div>

                        <!-- RFC -->
                        <div class="form-group mb-6 relative">
                            <label class="label-style">RFC del Plantel (Opcional)</label>
                            <div class="relative">
                                <input 
                                    v-model="form.plantel.rfc" 
                                    @blur="validateUniqueness('rfc')"
                                    type="text" 
                                    class="form-input uppercase font-mono border-slate-200 font-black text-red-900" 
                                    :class="{'border-red-600 bg-red-50 ring-2 ring-red-100': fieldValidation.rfc.error}"
                                    placeholder="XXXXXXXXXXXXX" 
                                    maxlength="13" 
                                    :disabled="loading"
                                >
                                <i v-if="validatingFields.rfc" class="fas fa-spinner fa-spin absolute right-4 top-1/2 -translate-y-1/2 text-red-600"></i>
                                <p v-if="fieldValidation.rfc.error" class="text-[9px] text-red-600 font-black mt-1 uppercase animate-pulse">
                                    <i class="fas fa-times-circle"></i> {{ fieldValidation.rfc.message }}
                                </p>
                            </div>
                        </div>

                        <!-- GPS REQUERIDO -->
                        <div class="p-6 rounded-[2rem] border transition-all duration-300 mb-6 shadow-sm"
                             :class="{
                                 'border-red-600 bg-red-50 ring-4 ring-red-100': attemptedSubmit && !form.plantel.latitud,
                                 'border-blue-100 bg-blue-50/20': !attemptedSubmit || form.plantel.latitud
                             }">
                            <div class="flex items-center justify-between mb-4">
                                <label class="text-[10px] font-black uppercase tracking-[0.2em]" 
                                       :class="attemptedSubmit && !form.plantel.latitud ? 'text-red-700' : 'text-blue-800'">
                                    <i class="fas fa-map-marker-alt mr-1"></i> Ubicación Geográfica
                                </label>
                                <span v-if="form.plantel.latitud" class="text-[9px] bg-green-100 text-green-700 px-3 py-1 rounded-full font-black uppercase shadow-sm">✓ Coordenadas Capturadas</span>
                                <!-- <span v-else-if="attemptedSubmit" class="text-[9px] bg-red-600 text-white px-3 py-1 rounded-full font-black uppercase animate-bounce">Requerido</span> -->
                            </div>
                            <br>
                            <button type="button" @click="getLocation" class="btn-primary w-full py-4 rounded-2xl flex items-center justify-center gap-3 shadow-lg transition-all" 
                                    :class="form.plantel.latitud ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700'"
                                    :disabled="gettingLocation || loading">
                                <i class="fas" :class="gettingLocation ? 'fa-spinner fa-spin' : (form.plantel.latitud ? 'fa-check-double' : 'fa-crosshairs')"></i>
                                <span class="font-black uppercase tracking-widest text-[11px]">
                                    {{ form.plantel.latitud ? 'Actualizar Coordenadas GPS' : 'Capturar Ubicación Obligatoria' }}
                                </span>
                            </button>
                        </div>

                        <div class="seccion-foto-modulo-premium">
                            <label style="font-size: 14px;"><strong>Fotografía del Plantel</strong></label>
                            
                            <div class="contenedor-dropzone-foto">
                                <input 
                                    type="file" 
                                    accept="image/*" 
                                    @change="handleFotoUpload" 
                                    class="input-nativo-oculto"
                                    :disabled="loading"
                                />

                                <div v-if="!fotoPreview" class="visual-vacio-foto">
                                    <div class="circulo-icono-camara">
                                        <i class="fas fa-image"></i>
                                    </div>
                                    <div class="textos-guia-drop">
                                        <p class="txt-principal-upload">Capturar o adjuntar fotografía</p>
                                        <p class="txt-secundario-upload">Formatos soportados: JPG, PNG, WEBP (Máx 4MB)</p>
                                    </div>
                                </div>

                                <div v-else class="visual-preview-foto">
                                    <div class="caja-miniatura-preview">
                                        <img :src="fotoPreview" class="imagen-render-preview" />
                                        <div class="capa-hover-cambio">
                                            <p class="txt-hover-cambio"><i class="fas fa-sync-alt mr-1"></i> Cambiar Imagen</p>
                                        </div>
                                    </div>
                                    
                                    <div class="barra-acciones-foto">
                                        <span class="badge-estatus-foto">
                                            <i class="fas fa-check-circle"></i> {{ fotoFile?.name ? (fotoFile.name.length > 20 ? fotoFile.name.substring(0,20)+'...' : fotoFile.name) : 'Imagen Lista' }}
                                        </span>
                                        <button type="button" @click.stop="removeFoto" class="btn-borrar-foto-premium">
                                            <i class="fas fa-times mr-1"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- NIVELES -->
                        <div class="form-group mb-6">
                            <label class="label-style">Niveles Educativos*</label>
                            <div v-if="loadingInitial" class="py-2 animate-pulse text-[10px] text-slate-400 font-black uppercase tracking-widest italic">Sincronizando catálogo...</div>
                            <div v-else class="grid grid-cols-2 gap-3">
                                <label v-for="nivel in nivelesCatalog" :key="nivel.id" 
                                    class="flex items-center gap-3 p-3 border-2 rounded-2xl cursor-pointer hover:border-red-200 transition-all shadow-sm bg-white" 
                                    :class="{'border-red-600 bg-red-50/30': form.plantel.niveles.includes(nivel.id), 'border-slate-50': !form.plantel.niveles.includes(nivel.id)}"
                                >
                                    <input type="checkbox" :value="nivel.id" v-model="form.plantel.niveles" class="w-4 h-4 accent-red-600">
                                    <span class="text-[10px] font-black uppercase text-slate-700 leading-none">{{ nivel.nombre }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group mb-6">
                            <label class="label-style">Estado*</label>
                            <select v-model="form.plantel.estado_id" class="form-input font-bold" required :disabled="loading">
                                <option value="">Seleccionar Estado...</option>
                                <option v-for="e in estados" :key="e.id" :value="e.id">{{ e.estado }}</option>
                            </select>
                        </div>

                        <div class="form-group mb-6">
                            <label class="label-style">Dirección Completa</label>
                            <textarea v-model="form.plantel.direccion" class="form-input font-medium" rows="2" placeholder="Calle, número, colonia, CP..." required minlength="10" :disabled="loading"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="grid grid-cols-3 gap-3">
                                <div class="form-group col-span-2 relative">
                                    <label class="label-style">Teléfono principal *</label>
                                    <div class="relative">
                                        <input 
                                            v-model="form.plantel.telefono" 
                                            @blur="validateUniqueness('telefono')"
                                            type="tel" 
                                            class="form-input font-bold" 
                                            :class="{'border-red-500 bg-red-50': fieldValidation.telefono.error}"
                                            placeholder="Número principal" 
                                            required 
                                            minlength="10" 
                                            maxlength="10"
                                            :disabled="loading"
                                        >
                                        <i v-if="validatingFields.telefono" class="fas fa-spinner fa-spin absolute right-4 top-1/2 -translate-y-1/2 text-red-600"></i>
                                        <p v-if="fieldValidation.telefono.error" class="text-[9px] text-red-600 font-black mt-1 uppercase animate-pulse">
                                            <i class="fas fa-times-circle"></i> {{ fieldValidation.telefono.message }}
                                        </p>
                                    </div>
                                </div>
                                <div class="form-group col-span-1">
                                    <label class="label-style">Extensión</label>
                                    <input 
                                        v-model="form.plantel.extension" 
                                        type="text" 
                                        class="form-input font-bold" 
                                        placeholder="Ext." 
                                        maxlength="6"
                                        :disabled="loading"
                                    >
                                </div>
                            </div>

                            <div class="form-group relative">
                                <label class="label-style">Teléfono Oficina *</label>
                                <input 
                                    v-model="form.plantel.tel_oficina" 
                                    type="tel" 
                                    class="form-input font-bold" 
                                    placeholder="Número de oficina" 
                                    required
                                    minlength="10" 
                                    maxlength="10"
                                    :disabled="loading"
                                >
                            </div>
                        </div>

                        <div class="form-group relative mt-6">
                            <label class="label-style">Correo Electrónico *</label>
                            <div class="relative">
                                <input 
                                    v-model="form.plantel.email" 
                                    @blur="validateUniqueness('email')"
                                    type="email" 
                                    class="form-input font-bold" 
                                    :class="{'border-red-500 bg-red-50': fieldValidation.email.error}"
                                    placeholder="Correo de contacto" 
                                    required 
                                    :disabled="loading"
                                >
                                <i v-if="validatingFields.email" class="fas fa-spinner fa-spin absolute right-4 top-1/2 -translate-y-1/2 text-red-600"></i>
                                <p v-if="fieldValidation.email.error" class="text-[9px] text-red-600 font-black mt-1 uppercase animate-pulse">
                                    <i class="fas fa-times-circle"></i> {{ fieldValidation.email.message }}
                                </p>
                            </div>
                        </div>

                        <div class="form-group mt-6">
                            <label class="label-style">Nombre del Director / Coordinador *</label>
                            <input v-model="form.plantel.director" type="text" placeholder="Nombre del director o coordinador" class="form-input font-bold" required minlength="3" :disabled="loading">
                        </div>
                    </div>

                    <!-- BLOQUE 2: DETALLES DE LA VISITA -->
                    <div class="space-y-8">
                        <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            <div class="section-title label-large text-black">
                                <i class="fas fa-handshake text-slate-800"></i> 2. Detalles de la Visita
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div class="form-group">
                                    <label class="label-style">Fecha de la Visita *</label>
                                    <input v-model="form.visita.fecha" type="date" class="form-input font-bold" required :disabled="loading">
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Persona Entrevistada *</label>
                                    <input v-model="form.visita.persona_entrevistada" type="text" class="form-input font-bold" placeholder="¿Quién nos atendió?" required minlength="3" :disabled="loading">
                                </div>
                            </div>

                            <div class="form-group mb-6">
                                <label class="label-style">Cargo / Puesto *</label>
                                <select v-model="form.visita.cargo" class="form-input font-bold" required :disabled="loading">
                                    <option value="Director/Coordinador">Director/Coordinador</option>
                                    <option value="Subdirector">Subdirector</option>
                                    <option value="Jefe de Departamento">Jefe de Departamento</option>
                                    <option value="Profesor">Profesor</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>

                            <div v-if="form.visita.cargo === 'Otro'" class="form-group mb-6 animate-fade-in">
                                <label class="label-style">Especifique el Cargo *</label>
                                <input v-model="form.visita.cargo_especifico" type="text" minlength="10" required class="form-input font-bold border-red-100" placeholder="Escriba el puesto real..." :disabled="loading">
                            </div>
                        </div>

                        <!-- LIBROS DE INTERÉS Y MUESTRAS -->
                        <div class="space-y-10 animate-fade-in pb-20">
    
                            <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                                <div class="bg-slate-50 p-6 rounded-[2.5rem] border border-slate-100 mb-6 relative" style="overflow: visible !important;">
                                    <label class="label-mini label-large mb-4 text-slate-600 font-black tracking-tighter uppercase">
                                        <i class="fas fa-eye mr-1 text-blue-500"></i> 3. Libros de Interés
                                    </label>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                                        <div class="form-group">
                                            <label class="label-mini">Filtrar por Serie</label>
                                            <select v-model="selectedSerieIdA" class="form-input font-bold text-xs lbb" @change="handleSerieChange('interest')">
                                                <option value="">Cualquier serie...</option>
                                                <option v-for="s in seriesFiltradas" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                                                <option value="otro">VER TODAS LAS SERIES</option>
                                            </select>
                                        </div>
                                        <div class="form-group relative">
                                            <label class="label-mini">Buscar y Añadir Libro</label>
                                            <div class="relative">
                                                <input 
                                                    v-model="interestInput.titulo" 
                                                    type="text" 
                                                    class="form-input pr-10 font-bold lbb" 
                                                    placeholder="Título o ISBN..." 
                                                    @input="searchBooks($event, 'interest')"
                                                    autocomplete="off"
                                                >
                                                <i v-if="searchingInterest" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                            </div>
                                            <ul v-if="interestSuggestions.length" class="autocomplete-list shadow-2xl border border-slate-100">
                                                <li v-for="b in interestSuggestions" :key="b.id" @click="addMaterial(b, 'interest')" class="text-[11px] font-black uppercase text-slate-700 hover:bg-blue-50 p-3 transition-colors">
                                                    <div class="flex justify-between items-center w-full">
                                                        <span class="truncate uppercase">{{ b.titulo }}</span>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div v-if="selectedInterestBooks.length" class="table-container mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                        <div class="table-responsive border-none">
                                            <table class="w-full divide-y divide-gray-200 responsive-table">
                                                <thead class="bg-slate-900 hidden md:table-header-group">
                                                    <tr>
                                                        <th class="table-header text-white">Libro</th>
                                                        <th class="table-header text-center w-32 text-white">Formato</th>
                                                        <th class="table-header text-center w-48 text-white">Opción Comercial *</th>
                                                        <th class="table-header text-center w-28 text-white">Cantidad / Valor *</th>
                                                        <th class="px-6 py-3 w-20 text-white"></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-100 block md:table-row-group">
                                                    <tr v-for="(item, idx) in selectedInterestBooks" :key="idx" 
                                                        class="hover:bg-gray-50 transition-colors block md:table-row relative p-5 md:p-0 border-b md:border-none">
                                                        
                                                        <td class="table-cell block md:table-cell" data-label="LIBRO">
                                                            <div class="text-xs font-black text-slate-800 uppercase leading-tight">{{ item.titulo }}</div>
                                                            <div class="text-[9px] font-black text-slate-400 uppercase tracking-tighter mt-1">{{ item.serie_nombre }}</div>
                                                        </td>

                                                        <td class="table-cell text-left md:text-center block md:table-cell" data-label="FORMATO">
                                                            <select v-model="item.tipo" class="select-table lbb md:max-w-[120px] md:mx-auto">
                                                                <option v-if="item.original_type === 'digital'" value="digital">DIGITAL</option>
                                                                <template v-else>
                                                                    <option value="fisico">FÍSICO</option>
                                                                    <option value="paquete">PAQUETE</option>
                                                                    <option value="por_revisar">POR REVISAR</option>
                                                                </template>
                                                            </select>
                                                        </td>

                                                        <td class="table-cell text-left md:text-center block md:table-cell" data-label="OPCIÓN COMERCIAL">
                                                            <select v-model="item.beneficio_tipo" class="select-table lbb md:max-w-[170px] md:mx-auto" required>
                                                                <option value="" disabled>Seleccione una opción...</option>
                                                                <option value="Precio especial">Precio especial</option>
                                                                <option value="Descuento por libro">Descuento por libro</option>
                                                            </select>
                                                        </td>

                                                        <td class="table-cell text-left md:text-center block md:table-cell" data-label="VALOR">
                                                            <input 
                                                                v-model.number="item.beneficio_valor" 
                                                                type="number" 
                                                                step="any" 
                                                                min="0" 
                                                                class="input-table text-center font-black lbb md:max-w-[90px] md:mx-auto" 
                                                                :placeholder="item.beneficio_tipo === 'Descuento por libro' ? '%' : '$'"
                                                                required
                                                            />
                                                        </td>

                                                        <td class="table-cell text-right block md:table-cell">
                                                            <button type="button" @click="selectedInterestBooks.splice(idx, 1)" class="btn-secondary flex items-center justify-end gap-1 w-full text-[10px] font-black uppercase text-red-400 hover:text-red-600">
                                                                <i class="fas fa-trash-alt"></i> <span class="md:hidden">Quitar</span>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 form-section bg-red-50/30 p-6 rounded-[2.5rem] border border-red-100 relative" style="overflow: visible !important;">
                                <label class="label-mini mb-4 text-red-800 label-large font-black tracking-tighter uppercase">
                                    <i class="fas fa-box-open mr-1"></i> 4. MUESTRAS DE PROMOCIÓN ENTREGADAS
                                </label>
                                
                                <div class="form-group relative mb-4">
                                    <label class="label-mini">Buscar Libro para Entrega Física</label>
                                    <div class="relative">
                                        <input 
                                            v-model="deliveredInput.titulo" 
                                            type="text" 
                                            class="form-input pr-10 font-bold border-red-100 shadow-sm lbb" 
                                            placeholder="Escribe título o ISBN..." 
                                            @input="searchBooks($event, 'delivered')"
                                            autocomplete="off"
                                        >
                                        <i v-if="searchingDelivered" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-400"></i>
                                    </div>
                                    <ul v-if="deliveredSuggestions.length" class="autocomplete-list shadow-2xl border border-red-100">
                                        <li v-for="b in deliveredSuggestions" :key="b.id" @click="addMaterial(b, 'delivered')" class="text-[11px] font-black uppercase text-slate-700 hover:bg-red-50 p-3 transition-colors">
                                            <div class="flex justify-between items-center w-full">
                                                <span class="truncate uppercase">{{ b.titulo }}</span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div v-if="selectedDeliveredBooks.length" class="table-modern-wrapper mt-6 overflow-hidden rounded-2xl border border-red-100 bg-white shadow-sm">
                                    <div class="table-responsive border-none">
                                        <table class="w-full divide-y divide-gray-200 responsive-table">
                                            <thead class="bg-red-900 hidden md:table-header-group">
                                                <tr>
                                                    <th class="table-header text-white">Libro</th>
                                                    <th class="table-header text-center w-40 text-white">Cantidad</th>
                                                    <th class="px-6 py-3 w-20 text-white"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-red-50 block md:table-row-group">
                                                <tr v-for="(item, idx) in selectedDeliveredBooks" :key="idx" class="hover:bg-red-50/20 transition-colors block md:table-row relative p-5 md:p-0 border-b md:border-none">
                                                    
                                                    <td class="table-cell block md:table-cell" data-label="LIBRO">
                                                        <div class="text-xs font-black text-slate-800 uppercase leading-tight">{{ item.titulo }}</div>
                                                    </td>

                                                    <td class="table-cell text-left md:text-center block md:table-cell" data-label="CANTIDAD">
                                                        <div class="flex justify-start md:justify-center">
                                                            <div class="quantity-control-wrapper w-full md:w-32">
                                                                <input v-model.number="item.cantidad" type="number" min="1" class="input-table text-center font-black lbb" />
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td class="table-cell text-right block md:table-cell">
                                                        <button type="button" @click="selectedDeliveredBooks.splice(idx, 1)" class="btn-secondary flex items-center justify-end gap-1 w-full text-[10px] font-black uppercase text-red-400 hover:text-red-600">
                                                            <i class="fas fa-trash-alt"></i> <span class="md:hidden">Quitar</span>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            <label class="label-mini mb-4 text-red-800 label-large font-black tracking-tighter"><i class="fas fa-box-open mr-1"></i> 5. RESULTADO Y COMENTARIOS DE LA SESIÓN</label>
                            
                            <div class="form-group mb-6">
                                <label class="label-style">Resolución / Resultado</label>
                                <select v-model="form.visita.resultado_visita" class="form-input font-black uppercase tracking-widest text-slate-700" required :disabled="loading">
                                    <option value="seguimiento">CONTINUAR SEGUIMIENTO</option>
                                    <option value="compra">DECISIÓN DE COMPRA</option>
                                    <option value="rechazo">NO INTERESADO </option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="label-style">COMENTARIOS Y ACUERDOS DE LA SESIÓN</label>
                                <textarea v-model="form.visita.comentarios" class="form-input font-medium" rows="4" placeholder="Resumen detallado de la entrevista (Mínimo 20 caracteres)..." required minlength="20" :disabled="loading"></textarea>
                            </div>
                        </div>
                        <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            <label class="label-mini mb-4 text-red-800 label-large font-black tracking-tighter"><i class="fas fa-box-open mr-1"></i> 6. BENEFICIOS PARA EL CLIENTE</label>
                            
                            <div class="form-group mb-6">
                                <textarea 
                                    v-model="form.plantel.beneficios_adicionales" 
                                    class="form-input font-medium" 
                                    rows="4" 
                                    placeholder="Escriba aquí los beneficios adicionales otorgados al cliente (Mínimo 20 caracteres)..." 
                                    required 
                                    minlength="20" 
                                    :disabled="loading"
                                ></textarea>
                            </div>
                        </div>
                        <div v-if="form.visita.resultado_visita === 'seguimiento'" class="form-group mb-6 p-6 bg-orange-50 rounded-[2.5rem] border-2 border-orange-100 animate-fade-in shadow-inner">
                            <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                                <label class="label-mini mb-4 text-red-800 label-large font-black tracking-tighter"><i class="fas fa-box-open mr-1"></i> 7. PROXIMO COMPROMISO</label>
                           
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[9px] text-orange-600 font-black uppercase mb-1 block">Fecha de la proxima visita</label>
                                        <input v-model="form.visita.proxima_visita" type="date" class="form-input border-orange-200 font-bold" required :disabled="loading">
                                    </div>
                                    <div>
                                        <label class="text-[9px] text-orange-600 font-black uppercase mb-1 block">Objetivo</label>
                                        <select v-model="form.visita.proxima_accion" class="form-input border-orange-200 font-bold" :disabled="loading">
                                            <option value="visita">Visita de Seguimiento</option>
                                            <option value="presentacion">Presentación Académica</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex flex-col md:flex-row justify-end gap-4 border-t border-slate-100 pt-8 pb-20">
                    <button type="button" @click="$router.push('/visitas')" class="btn-secondary px-10 py-4 rounded-2xl font-bold uppercase text-xs tracking-widest bg-white border-2 border-slate-200 text-black" :disabled="loading">Cancelar</button>
                    <button type="submit" class="btn-primary px-20 py-4 shadow-xl shadow-red-900/10 transition-all active:scale-95" :disabled="loading || anyDuplicate || isProcessingCheck || gettingLocation">
                        <i class="fas" :class="loading ? 'fa-spinner fa-spin mr-2' : 'fa-cloud-upload-alt mr-2'"></i> 
                        {{ loading ? 'Sincronizando...' : 'Finalizar Registro y Alta' }}
                    </button>
                </div>
            </form>
        </div>

        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="showSuccessModal" class="modal-overlay-custom">
                    <div class="modal-content-success animate-scale-in">
                        <div class="success-icon-wrapper shadow-lg shadow-green-100"><i class="fas fa-check"></i></div>
                        <h2 class="modal-title-success">¡Alta Exitosa!</h2>
                        <p class="modal-text-success">El plantel se ha registrado como <strong>{{ savedClientType }}</strong> y la bitácora ha sido almacenada correctamente.</p>
                        <button @click="goToHistory" class="btn-primary w-full mt-8 bg-slate-900 border-none text-white font-black uppercase text-xs tracking-widest py-4">Regresar al Listado</button>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="errorMessage" class="modal-overlay-custom">
                    <div class="modal-content-error modal-content-success animate-scale-in">
                        <div class="error-icon-wrapper shadow-lg shadow-red-100"><i class="fas fa-exclamation-triangle"></i></div>  
                        <h2 class="modal-title-error">¡Atención!</h2>
                        <p class="modal-text-error" v-html="errorMessage"></p>
                        <button @click="errorMessage = null" class="btn-primary w-full mt-8 bg-red-600 border-none text-white font-black uppercase text-xs tracking-widest py-4">Cerrar Mensaje</button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const loading = ref(false);
const loadingInitial = ref(true);
const gettingLocation = ref(false);
const showSuccessModal = ref(false);
const errorMessage = ref(null);
const isProcessingCheck = ref(false); 
const attemptedSubmit = ref(false); 

// Referencias reactivas para el manejo y preview de la foto
const fotoFile = ref(null);
const fotoPreview = ref(null);

const checkStates = reactive({
    name:  { checking: false, isDuplicate: false, verified: false },
    rfc:   { checking: false, isDuplicate: false, verified: false },
    email: { checking: false, isDuplicate: false, verified: false },
    phone: { checking: false, isDuplicate: false, verified: false }
});

const fieldValidation = reactive({ 
    name: { error: false, message: '' },
    rfc: { error: false, message: '' }, 
    email: { error: false, message: '' }, 
    telefono: { error: false, message: '' } 
});

const validatingFields = reactive({
    name: false,
    rfc: false,
    email: false,
    telefono: false
});

const anyDuplicate = computed(() => {
    return fieldValidation.name.error || fieldValidation.rfc.error || fieldValidation.email.error || fieldValidation.telefono.error;
});

const searchingInterest = ref(false);
const searchingDelivered = ref(false);

const estados = ref([]);
const nivelesCatalog = ref([]); 
const allSeries = ref([]); 

const selectedSerieIdA = ref(''); 
const interestSuggestions = ref([]);
const deliveredSuggestions = ref([]);
const selectedInterestBooks = ref([]);
const selectedDeliveredBooks = ref([]);

const interestInput = reactive({ titulo: '' });
const deliveredInput = reactive({ titulo: '' });

let bookTimer = null;

const form = reactive({
    plantel: {
        name: '',
        rfc: '',
        latitud: null,
        longitud: null,
        niveles: [],
        estado_id: '',
        direccion: '',
        telefono: '',    
        tel_oficina: '', 
        extension: '',   
        email: '',
        director: '',
        beneficios_adicionales: ''
    },
    visita: {
        fecha: '',
        persona_entrevistada: '',
        cargo: 'Director/Coordinador',
        cargo_especifico: '',
        resultado_visita: 'seguimiento',
        comentarios: '',
        proxima_visita: '',
        proxima_accion: 'visita'
    }
});

const seriesFiltradas = computed(() => {
    if (form.plantel.niveles.length === 0) return [];
    return allSeries.value.filter(serie => form.plantel.niveles.includes(serie.nivel_educativo_id));
});

const savedClientType = computed(() => form.visita.resultado_visita === 'compra' ? 'Cliente' : 'Prospecto');

// Funciones para gestionar el archivo de foto
const handleFotoUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (file.size > 4 * 1024 * 1024) {
        alert("La imagen excede el límite permitido de 4MB.");
        return;
    }

    fotoFile.value = file;
    fotoPreview.value = URL.createObjectURL(file);
};

const removeFoto = () => {
    fotoFile.value = null;
    if (fotoPreview.value) {
        URL.revokeObjectURL(fotoPreview.value);
        fotoPreview.value = null;
    }
};

const validateUniqueness = async (field) => {
    let val = '';
    let queryParam = field; 
    let minLen = 5;

    if (field === 'name') { val = form.plantel.name?.trim() || ''; queryParam = 'nombre'; minLen = 5; }
    else if (field === 'rfc') { val = form.plantel.rfc?.trim().toUpperCase() || ''; queryParam = 'rfc'; minLen = 12; }
    else if (field === 'email') { val = form.plantel.email?.trim().toLowerCase() || ''; queryParam = 'correo'; minLen = 5; }
    else if (field === 'telefono') { val = form.plantel.telefono?.trim() || ''; queryParam = 'telefono'; minLen = 10; }

    if (!val || val.length < minLen) {
        if (fieldValidation[field]) {
            fieldValidation[field].error = false;
            fieldValidation[field].message = '';
        }
        return;
    }

    validatingFields[field] = true;
    isProcessingCheck.value = true;
    errorMessage.value = null;

    try {
        const res = await axios.get('/search/receptores/check-rfc', { params: { [queryParam]: val } });
        
        if (res.data.status === 'conflict') {
            fieldValidation[field].error = true;
            fieldValidation[field].message = res.data.message;
        } else {
            fieldValidation[field].error = false;
            fieldValidation[field].message = '';
        }
    } catch (e) { 
        if (fieldValidation[field]) fieldValidation[field].error = false; 
    } finally { 
        validatingFields[field] = false; 
        isProcessingCheck.value = false; 
    }
};

watch(() => fieldValidation.name.error, (val) => checkStates.name.isDuplicate = val);
watch(() => fieldValidation.rfc.error, (val) => checkStates.rfc.isDuplicate = val);
watch(() => fieldValidation.email.error, (val) => checkStates.email.isDuplicate = val);
watch(() => fieldValidation.telefono.error, (val) => checkStates.phone.isDuplicate = val);

const fetchInitialData = async () => {
    loadingInitial.value = true;
    try {
        const [resEst, resNiv, resSer] = await Promise.all([
            axios.get('/estados'), axios.get('/search/niveles'), axios.get('/search/series')
        ]);
        estados.value = resEst.data;
        nivelesCatalog.value = resNiv.data;
        allSeries.value = resSer.data;
    } catch (e) { console.error(e); } finally { loadingInitial.value = false; }
};

const getLocation = () => {
    if (!navigator.geolocation) return alert("Navegador no soporta GPS.");
    gettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
        (p) => { 
            form.plantel.latitud = p.coords.latitude; 
            form.plantel.longitud = p.coords.longitude; 
            gettingLocation.value = false; 
            errorMessage.value = null; 
        },
        () => { gettingLocation.value = false; errorMessage.value = "GPS DENEGADO."; }, 
        { enableHighAccuracy: true }
    );
};

const searchBooks = (event, type) => {
    const val = event.target.value;
    if (val.length < 3) return type === 'interest' ? interestSuggestions.value = [] : deliveredSuggestions.value = [];
    if (type === 'interest') searchingInterest.value = true; else searchingDelivered.value = true;
    if (bookTimer) clearTimeout(bookTimer);
    const sId = type === 'interest' ? (selectedSerieIdA.value === 'otro' ? null : selectedSerieIdA.value) : null; 
    bookTimer = setTimeout(async () => {
        try {
            const res = await axios.get('search/libros', { params: { query: val, serie_id: sId } });
            if (type === 'interest') interestSuggestions.value = res.data.filter(b => b.type !== 'promocion');
            else deliveredSuggestions.value = res.data.filter(b => b.type === 'promocion');
        } catch (e) { console.error(e); } finally { searchingInterest.value = false; searchingDelivered.value = false; }
    }, 400);
};

const addMaterial = (book, type) => {
    const serie = allSeries.value.find(s => s.id == book.serie_id);
    const sNom = serie ? (serie.nombre || serie.serie) : 'Sin Serie';
    if (type === 'interest') {
        if (!selectedInterestBooks.value.find(b => b.id === book.id)) {
            selectedInterestBooks.value.push({ 
                id: book.id, 
                titulo: book.titulo, 
                serie_nombre: sNom, 
                original_type: book.type, 
                tipo: 'fisico',
                beneficio_tipo: '', // Campo requerido para el Tipo de Beneficio
                beneficio_valor: '' // Campo requerido para la Cantidad/Precio/Porcentaje
            });
        }
        interestInput.titulo = ''; interestSuggestions.value = [];
    } else {
        if (!selectedDeliveredBooks.value.find(b => b.id === book.id)) {
            selectedDeliveredBooks.value.push({ id: book.id, titulo: book.titulo, serie_nombre: sNom, cantidad: 1 });
        }
        deliveredInput.titulo = ''; deliveredSuggestions.value = [];
    }
};

const handleSubmit = async () => {
    attemptedSubmit.value = true;
    errorMessage.value = null;

    if (anyDuplicate.value) {
        const active = Object.values(fieldValidation).find(f => f.error);
        errorMessage.value = active ? active.message : "DATOS DUPLICADOS EN EL SISTEMA GLOBAL.";
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    // if (!form.plantel.latitud) {
    //     errorMessage.value = "GPS OBLIGATORIO.";
    //     window.scrollTo({ top: 0, behavior: 'smooth' });
    //     return;
    // }

    if (selectedInterestBooks.value.length === 0) {
        errorMessage.value = "AGREGUE AL MENOS UN MATERIAL DE INTERÉS.";
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    // NUEVA VALIDACIÓN: Validar los campos de precio especial o descuento obligatorios en libros de interés
    const camposIncompletos = selectedInterestBooks.value.some(libro => !libro.beneficio_tipo || libro.beneficio_valor === undefined || libro.beneficio_valor === '');
    if (camposIncompletos) {
        errorMessage.value = "POR FAVOR, ESPECIFIQUE EL TIPO DE BENEFICIO Y EL VALOR EN TODOS LOS LIBROS DE INTERÉS.";
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    // NUEVA VALIDACIÓN: Validar longitud mínima de Beneficios Adicionales
    if (!form.plantel.beneficios_adicionales || form.plantel.beneficios_adicionales.trim().length < 20) {
        errorMessage.value = "EL CAMPO DE 'BENEFICIOS ADICIONALES' ES OBLIGATORIO Y DEBE CONTENER AL MENOS 20 CARACTERES.";
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    if (form.plantel.niveles.length === 0) { 
        errorMessage.value = "SELECCIONE NIVELES EDUCATIVOS."; 
        window.scrollTo({ top: 0, behavior: 'smooth' }); 
        return; 
    }

    loading.value = true;
    try {
        const nivelNombres = nivelesCatalog.value
            .filter(n => form.plantel.niveles.includes(n.id))
            .map(n => n.nombre);

        const finalCargo = form.visita.cargo === 'Otro' ? form.visita.cargo_especifico : form.visita.cargo;

        // Construcción del FormData respetando de forma idéntica los tipos de datos originales
        const formData = new FormData();
        
        formData.append('plantel[name]', form.plantel.name);
        formData.append('plantel[rfc]', form.plantel.rfc);
        formData.append('plantel[latitud]', form.plantel.latitud);
        formData.append('plantel[longitud]', form.plantel.longitud);
        formData.append('plantel[estado_id]', form.plantel.estado_id);
        formData.append('plantel[direccion]', form.plantel.direccion);
        formData.append('plantel[telefono]', form.plantel.telefono);
        formData.append('plantel[tel_oficina]', form.plantel.tel_oficina);
        formData.append('plantel[extension]', form.plantel.extension);
        formData.append('plantel[email]', form.plantel.email.toLowerCase());
        formData.append('plantel[director]', form.plantel.director);
        formData.append('plantel[beneficios_adicionales]', form.plantel.beneficios_adicionales);

        nivelNombres.forEach((nivel, index) => {
            formData.append(`plantel[niveles][${index}]`, nivel);
        });

        formData.append('visita[fecha]', form.visita.fecha);
        formData.append('visita[persona_entrevistada]', form.visita.persona_entrevistada);
        formData.append('visita[cargo]', finalCargo);
        formData.append('visita[resultado_visita]', form.visita.resultado_visita);
        formData.append('visita[comentarios]', form.visita.comentarios);
        formData.append('visita[proxima_visita]', form.visita.proxima_visita);
        formData.append('visita[proxima_accion]', form.visita.proxima_accion);
        
        // CORRECCIÓN AQUÍ: Se mapean uno a uno de vuelta a un array iterable para que Laravel lo acepte como array normal.
        selectedInterestBooks.value.forEach((libro, index) => {
            formData.append(`visita[libros_interes][interes][${index}][id]`, libro.id);
            formData.append(`visita[libros_interes][interes][${index}][titulo]`, libro.titulo);
            formData.append(`visita[libros_interes][interes][${index}][serie_nombre]`, libro.serie_nombre);
            formData.append(`visita[libros_interes][interes][${index}][tipo]`, libro.tipo);
            formData.append(`visita[libros_interes][interes][${index}][beneficio_tipo]`, libro.beneficio_tipo);
            formData.append(`visita[libros_interes][interes][${index}][beneficio_valor]`, libro.beneficio_valor);
        });

        selectedDeliveredBooks.value.forEach((libro, index) => {
            formData.append(`visita[libros_interes][entregado][${index}][id]`, libro.id);
            formData.append(`visita[libros_interes][entregado][${index}][titulo]`, libro.titulo);
            formData.append(`visita[libros_interes][entregado][${index}][cantidad]`, libro.cantidad);
        });

        // ÚNICA ADICIÓN: La foto si existe
        if (fotoFile.value) {
            formData.append('plantel[foto_plantel]', fotoFile.value);
        }

        await axios.post('/visitas/primera', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        
        showSuccessModal.value = true;
    } catch (e) {
        errorMessage.value = e.response?.data?.message || "Error al procesar el alta.";
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } finally { loading.value = false; }
};

const goToHistory = () => { showSuccessModal.value = false; router.push('/visitas'); };
const handleSerieChange = (type) => { if (type === 'interest') { interestInput.titulo = ''; interestSuggestions.value = []; } };

onMounted(fetchInitialData);
</script>

<style scoped>
.form-section { background: #fff; padding: 30px; border-radius: 32px; border: 1px solid #f1f5f9; }
.section-title { font-weight: 900; font-size: 1.1rem; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 1px; }

.label-style { @apply text-[10px] font-black text-red-600 uppercase tracking-widest mb-2 block; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block tracking-widest; }

.form-input { width: 100%; padding: 14px 18px; border-radius: 16px; border: 2px solid #f1f5f9; font-weight: 700; color: #000000; background: #fafbfc; transition: all 0.2s; font-size: 0.9rem; }
.form-input:focus { border-color: #000000; background: white; outline: none; box-shadow: 0 0 0 4px rgba(0,0,0,0.02); }

.btn-primary { background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.autocomplete-list { position: absolute; z-index: 2000; width: 100%; background: white; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.2); max-height: 250px; overflow-y: auto; list-style: none; padding: 10px; margin: 8px 0 0; }
.autocomplete-list li { padding: 12px 16px; cursor: pointer; border-radius: 12px; border-bottom: 1px solid #f8fafc; transition: all 0.2s; }
.autocomplete-list li:hover { background: #fef2f2; color: #b91c1c; }

.modal-overlay-custom { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
.modal-content-success { background: white; padding: 45px; border-radius: 40px; width: 90%; max-width: 450px; text-align: center; box-shadow: 0 30px 60px -12px rgba(0,0,0,0.4); border: 1px solid #f1f5f9; }
.success-icon-wrapper { width: 80px; height: 80px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 25px; border: 4px solid white; }
.modal-title-success { font-size: 1.75rem; font-weight: 900; color: #000000; margin-bottom: 12px; }
.modal-text-success { color: #64748b; font-size: 0.95rem; line-height: 1.6; font-weight: 500; }

.shadow-premium { box-shadow: 0 15px 35px -10px rgba(0,0,0,0.05); }

.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

select { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23dc2626' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 1rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem; appearance: none; }

.table-header { padding: 14px 16px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; text-align: left; }
.table-cell { padding: 12px 16px; vertical-align: middle; }

.input-table, .select-table { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 10px; font-weight: 900; color: #1e293b; padding: 6px 8px; text-transform: uppercase; transition: all 0.2s; width: 100%; }
.input-table:focus, .select-table:focus { outline: none; border-color: #f87171; background-color: #fff; box-shadow: 0 0 0 2px rgba(248, 113, 113, 0.1); }

.btn-icon-delete { background: none; border: none; color: #cbd5e1; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: color 0.2s; display: inline-flex; align-items: center; gap: 4px; }
.btn-icon-delete:hover { color: #dc2626; }
.label-large { display: block; font-size: 0.79rem; font-weight: 900; text-transform: uppercase; color: #000000; margin-bottom: 6px; letter-spacing: 0.12em; opacity: 0.8; }

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
.error-icon-wrapper { width: 80px; height: 80px; background: #fee2e2; color: #b91c1c; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 25px; border: 4px solid white; }
.modal-title-error { font-size: 1.75rem; font-weight: 900; color: #b91c1c; margin-bottom: 12px; text-transform: uppercase; }
.modal-text-error { color: #991b1b; font-size: 0.95rem; line-height: 1.6; font-weight: 700; text-transform: uppercase; }

/* =======================================================
   REGLAS CSS INDEPENDIENTES PARA LA SECCIÓN DE FOTOGRAFÍA
   ======================================================= */
.seccion-foto-modulo-premium {
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid #e2e8f0;
  border-radius: 24px;
  padding: 20px;
  margin-top: 24px;
  margin-bottom: 24px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.contenedor-dropzone-foto {
  position: relative;
  border: 2px dashed #cbd5e1;
  background-color: #ffffff;
  border-radius: 16px;
  padding: 24px;
  min-height: 150px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  overflow: hidden;
}

.contenedor-dropzone-foto:hover {
  border-color: #f87171;
  background-color: #fffafb;
}

.input-nativo-oculto {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
  z-index: 10;
}

.visual-vacio-foto {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  pointer-events: none;
}

.circulo-icono-camara {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background-color: #fef2f2;
  color: #ef4444;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  box-shadow: 0 2px 4px rgba(239, 68, 68, 0.1);
  transition: background-color 0.3s ease;
}

.contenedor-dropzone-foto:hover .circulo-icono-camara {
  background-color: #fee2e2;
}

.textos-guia-drop {
  text-align: center;
}

.txt-principal-upload {
  font-size: 11px;
  font-weight: 900;
  text-transform: uppercase;
  color: #334155;
  letter-spacing: 0.05em;
}

.txt-secundario-upload {
  font-size: 9px;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: -0.01em;
  margin-top: 4px;
}

.visual-preview-foto {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  position: relative;
  z-index: 20;
}

.caja-miniatura-preview {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  border: 4px solid #ffffff;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.imagen-render-preview {
  max-height: 160px;
  object-fit: cover;
}

.capa-hover-cambio {
  position: absolute;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.2s ease;
  pointer-events: none;
}

.caja-miniatura-preview:hover .capa-hover-cambio {
  opacity: 1;
}

.txt-hover-cambio {
  color: #ffffff;
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.barra-acciones-foto {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.badge-estatus-foto {
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
  color: #15803d;
  background-color: #f0fdf4;
  border: 1px solid #dcfce7;
  padding: 4px 12px;
  border-radius: 9999px;
  display: flex;
  align-items: center;
  gap: 4px;
  letter-spacing: 0.05em;
}

.btn-borrar-foto-premium {
  background-color: #0f172a;
  color: #ffffff;
  font-size: 9px;
  font-weight: 900;
  text-transform: uppercase;
  padding: 4px 12px;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: background-color 0.2s ease;
}

.btn-borrar-foto-premium:hover {
  background-color: #dc2626;
}
</style>