<template>
    <div class="content-wrapper p-2 md:p-6 bg-slate-50 min-h-screen">
        <div class="module-page max-w-7xl mx-auto">
            <!-- Encabezado -->
            <div class="module-header flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-black uppercase tracking-tighter">
                        Modificar {{ visita?.es_primera_visita ? 'Primera Visita' : 'Seguimiento' }}
                    </h1>
                    <p class="text-xs md:text-sm text-red-600 font-bold uppercase tracking-widest mt-1">
                        {{ visita?.es_primera_visita ? 'Apertura: Todos los campos son editables.' : 'Seguimiento: Datos del plantel protegidos (Solo lectura).' }}
                    </p>
                </div>
                <button @click="$router.back()" class="btn-secondary shadow-sm shrink-0 w-full sm:w-auto">
                    <i class="fas fa-arrow-left"></i> Cancelar y Volver
                </button>
            </div>

            <!-- Loader de hidratación -->
            <div v-if="loadingInitial" class="py-20 text-center animate-pulse">
                <i class="fas fa-circle-notch fa-spin text-5xl text-red-600 mb-4"></i>
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs italic">Recuperando expediente del servidor...</p>
            </div>

            <!-- BLOQUEO POR REGLA DE NEGOCIO (Seguimiento ya editado) -->
            <div v-else-if="isLocked" class="py-20 text-center bg-white rounded-[2.5rem] shadow-premium border-2 border-red-100 animate-fade-in mx-2">
                <div class="w-20 h-20 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-lock fa-3xl"></i>
                </div>
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Acceso Restringido</h2>
                <p class="text-slate-500 max-w-md mx-auto mt-4 font-medium px-6 text-sm">
                    Este seguimiento ya fue modificado una vez. Por políticas de integridad, no se permiten más ajustes manuales.
                </p>
                <button @click="$router.push('/visitas')" class="mt-8 btn-primary px-12 py-4 text-xs font-black uppercase tracking-widest">Regresar al Historial</button>
            </div>

            <form v-else @submit.prevent="handleSubmit">
                
               

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- BLOQUE 1: DATOS DEL PLANTEL -->
                    <div class="form-section shadow-premium border-t-8 border-t-red-700 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                        <div class="section-title label-large text-black">
                            <i class="fas fa-school text-red-700"></i>1. Datos del Plantel
                            <span v-if="!visita.es_primera_visita" class="ml-auto text-[8px] bg-slate-100 text-slate-500 px-2 py-1 rounded font-black">PROTEGIDO</span>
                        </div>
                        
                        <div class="form-group mb-6 relative">
                            <label class="label-style">Nombre del Plantel *</label>
                            <input v-model="form.plantel.name" @blur="validateUniqueness('name')" type="text" class="form-input font-bold uppercase" :class="fieldValidation.name.error ? 'border-red-600 bg-red-50' : ''" :disabled="!visita.es_primera_visita" required>
                            <p v-if="fieldValidation.name.error" class="text-[9px] text-red-600 font-black mt-1 uppercase animate-pulse">
                                <i class="fas fa-times-circle"></i> {{ fieldValidation.name.message }}
                            </p>
                        </div>

                        <div class="form-group mb-6 relative">
                            <label class="label-style">RFC del Plantel *</label>
                            <input v-model="form.plantel.rfc" @blur="validateUniqueness('rfc')" type="text" class="form-input uppercase font-mono border-red-100 font-black text-red-900" :class="fieldValidation.rfc.error ? 'border-red-600 bg-red-50' : ''" :disabled="!visita.es_primera_visita" required minlength="13" maxlength="13">
                            <p v-if="fieldValidation.rfc.error" class="text-[9px] text-red-600 font-black mt-1 uppercase animate-pulse">
                                <i class="fas fa-times-circle"></i> {{ fieldValidation.rfc.message }}
                            </p>
                        </div>

                        <!-- GPS -->
                        <div class="p-6 rounded-[2rem] border transition-all duration-300 mb-6 shadow-sm"
                             :class="visita.es_primera_visita ? 'border-blue-100 bg-blue-50/20' : 'border-slate-100 bg-slate-50/50 opacity-60'">
                            <div class="flex items-center justify-between mb-4">
                                <label class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-800">
                                    <i class="fas fa-map-marker-alt mr-1"></i> Ubicación Geográfica *
                                </label>
                                <span v-if="form.plantel.latitud" class="text-[9px] bg-green-100 text-green-700 px-3 py-1 rounded-full font-black uppercase">✓ Registrada</span>
                            </div>
                            <button v-if="visita.es_primera_visita" type="button" @click="getLocation" class="btn-primary w-full py-4 rounded-2xl flex items-center justify-center gap-3 shadow-lg bg-blue-600 hover:bg-blue-700" :disabled="gettingLocation">
                                <i class="fas" :class="gettingLocation ? 'fa-spinner fa-spin' : 'fa-crosshairs'"></i>
                                <span class="font-black uppercase tracking-widest text-[11px]">Actualizar Coordenadas</span>
                            </button>
                            <div v-else class="text-center font-mono text-[11px] font-black text-slate-400 py-2">
                                {{ form.plantel.latitud }}, {{ form.plantel.longitud }}
                            </div>
                        </div>
                        <div class="seccion-foto-modulo-premium mb-6">
                            <label style="font-size: 14px;"><strong>Fotografía del Plantel (Opcional)</strong></label>
                            
                            <div class="contenedor-dropzone-foto">
                                <input 
                                    type="file" 
                                    accept="image/*" 
                                    @change="handleFotoUpload" 
                                    class="input-nativo-oculto"
                                    :disabled="!visita?.es_primera_visita" 
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
                                        <button type="button" @click.stop="removeFoto" class="btn-borrar-foto-premium" :disabled="!visita?.es_primera_visita">
                                            <i class="fas fa-times mr-1"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- NIVELES -->
                        <div class="form-group mb-6">
                            <label class="label-style">Niveles Educativos *</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label v-for="nivel in nivelesCatalog" :key="nivel.id" 
                                    class="flex items-center gap-3 p-3 border-2 rounded-2xl transition-all shadow-sm" 
                                    :class="form.plantel.niveles.includes(nivel.id) ? 'border-red-600 bg-red-50/30' : 'border-slate-50 bg-white'">
                                    <input type="checkbox" :value="nivel.id" v-model="form.plantel.niveles" class="w-4 h-4 accent-red-600" :disabled="!visita.es_primera_visita">
                                    <span class="text-[10px] font-black uppercase text-slate-700 leading-none">{{ nivel.nombre }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group mb-6">
                            <label class="label-style">Estado*</label>
                            <select v-model="form.plantel.estado_id" class="form-input font-bold" required :disabled="!visita.es_primera_visita">
                                <option v-for="e in estados" :key="e.id" :value="e.id">{{ e.estado }}</option>
                            </select>
                        </div>

                        <div class="form-group mb-6">
                            <label class="label-style">Dirección Completa</label>
                            <textarea v-model="form.plantel.direccion" class="form-input font-medium uppercase" rows="2" :disabled="!visita.es_primera_visita"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="label-style">Teléfono Principal*</label>
                                <input v-model="form.plantel.telefono" @blur="validateUniqueness('telefono')" type="tel" class="form-input font-bold" :class="fieldValidation.telefono.error ? 'border-red-600 bg-red-50' : ''" :disabled="!visita.es_primera_visita" minlength="10" maxlength="10" required>
                                <p v-if="fieldValidation.telefono.error" class="text-[9px] text-red-600 font-black mt-1 uppercase animate-pulse">
                                    <i class="fas fa-times-circle"></i> {{ fieldValidation.telefono.message }}
                                </p>
                            </div>
                            <div class="form-group">
                                <label class="label-style">Extensión *</label>
                                <input v-model="form.plantel.extension" type="text" class="form-input font-bold" :disabled="!visita.es_primera_visita" maxlength="5" required>
                            </div>
                            <div class="form-group">
                                <label class="label-style">Teléfono de Oficina *</label>
                                <input v-model="form.plantel.tel_oficina" type="text" class="form-input font-bold" :disabled="!visita.es_primera_visita" minlength="10" maxlength="10" required>
                            </div>
                            <div class="form-group">
                                <label class="label-style">Correo Electrónico *</label>
                                <input 
                                    v-model="form.plantel.email" 
                                    @blur="validateUniqueness('email')"
                                    type="email" 
                                    class="form-input font-bold" 
                                    style="text-transform: lowercase !important;"
                                    @input="form.plantel.email = form.plantel.email.toLowerCase()"
                                    :class="fieldValidation.email.error ? 'border-red-600 bg-red-50' : ''"
                                    :disabled="!visita.es_primera_visita" 
                                    required
                                >
                                <p v-if="fieldValidation.email.error" class="text-[9px] text-red-600 font-black mt-1 uppercase animate-pulse">
                                    <i class="fas fa-times-circle"></i> {{ fieldValidation.email.message }}
                                </p>
                            </div>
                        </div>

                        <div class="form-group mt-6">
                            <label class="label-style">Nombre del Director / Coordinador *</label>
                            <input v-model="form.plantel.director" type="text" class="form-input font-bold uppercase" :disabled="!visita.es_primera_visita" required>
                        </div>
                    </div>

                    <!-- BLOQUE 2: DETALLES DE LA VISITA -->
                    <div class="space-y-8">
                        <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            <div class="section-title  label-large text-black">
                                <i class="fas fa-handshake text-slate-800"></i> 2. Detalles de la Visita
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div class="form-group">
                                    <label class="label-style">Fecha de la Visita *</label>
                                    <input v-model="form.visita.fecha" type="date" class="form-input font-bold" required>
                                </div>
                                <div class="form-group">
                                    <label class="label-style">Persona Entrevistada *</label>
                                    <input v-model="form.visita.persona_entrevistada" type="text" class="form-input font-bold uppercase" required>
                                </div>
                            </div>

                            <div class="form-group mb-6">
       
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
                        </div>

                        <!-- LIBROS DE INTERÉS Y MUESTRAS (EDITABLES SIEMPRE) -->
                        <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            
                            <!-- INTERÉS (OBLIGATORIO SOLO SI ES PRIMERA VISITA) -->
                            <div class="bg-slate-50 p-6 rounded-[2.5rem] border border-slate-100 mb-6 relative" 
                                 :class="{'border-red-300 ring-2 ring-red-50': visita?.es_primera_visita && selectedInterestBooks.length === 0}"
                                 style="overflow: visible !important;">
                                <label class="label-mini label-large mb-4 text-slate-600 font-black tracking-tighter uppercase">
                                    <i class="fas fa-eye mr-1  text-blue-500"></i> 3. Libros de Interés 
                                    <span v-if="visita?.es_primera_visita" class="text-red-600 ml-1">* REQUERIDO</span>
                                </label>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                                    <div class="form-group">
                                        <select v-model="selectedSerieIdA" class="form-input font-bold text-xs" @change="handleSerieChange('interest')">
                                            <option value="">Serie (Opcional)...</option>
                                            <option v-for="s in allSeries" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                                            <option value="otro">VER TODAS LAS SERIES</option>
                                        </select>
                                    </div>
                                    <div class="form-group relative">
                                        <div class="relative">
                                            <input v-model="interestInput.titulo" type="text" class="form-input pr-10 font-bold" placeholder="Buscar y añadir..." @input="searchBooks($event, 'interest')" autocomplete="off">
                                            <i v-if="searchingInterest" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        </div>
                                        <ul v-if="interestSuggestions.length" class="autocomplete-list shadow-2xl border border-slate-100">
                                            <li v-for="b in interestSuggestions" :key="b.id" @click="addMaterial(b, 'interest')" class="text-[10px] font-black uppercase text-slate-700 hover:bg-blue-50 p-3 transition-colors border-b last:border-0 border-slate-50">
                                                {{ b.titulo }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div v-if="selectedInterestBooks.length" class="table-container mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                    <table class="w-full divide-y divide-gray-200">
                                        <thead class="bg-slate-900 text-white">
                                            <tr class="text-[9px] uppercase tracking-widest font-black">
                                                <th class="px-4 py-3 text-left">Libro</th>
                                                <th class="px-4 py-3 text-center w-32">Formato</th>
                                                <th class="px-4 py-3 text-center w-36">Opción Comercial</th>
                                                <th class="px-4 py-3 text-center w-28">Cantidad / Valor</th>
                                                <th class="px-4 py-3 w-12"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="(item, idx) in selectedInterestBooks" :key="idx" class="hover:bg-gray-50 transition-colors">
                                                <td class="table-cell">
                                                    <div class="text-[11px] font-black uppercase">{{ item.titulo }}</div>
                                                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-tighter mt-0.5">{{ item.serie_nombre }}</div>
                                                </td>
                                                <td class="table-cell text-center">
                                                    <select v-model="item.tipo" class="select-table" :disabled="item.original_type === 'digital'">
                                                        <option value="fisico">FÍSICO</option>
                                                        <option value="digital">POR REVISAR</option>
                                                        <option value="paquete">PAQUETE</option>
                                                    </select>
                                                </td>
                                                <td class="table-cell text-center">
                                                    <select v-model="item.beneficio_tipo" class="select-table" required>
                                                        <option value="Precio especial">Precio especial</option>
                                                        <option value="Descuento por libro">Descuento por libro</option>
                                                    </select>
                                                </td>
                                                <td class="table-cell text-center">
                                                    <input v-model.number="item.beneficio_valor" type="number" min="0" class="input-table text-center" required />
                                                </td>
                                                <td class="table-cell text-center">
                                                    <button type="button" @click="selectedInterestBooks.splice(idx, 1)" class="text-red-300 btn-secondary hover:text-red-600 transition-colors"><i class="fas fa-trash-alt"></i>Quitar</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            </div>
                            <div class="mt-6 form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            <div class="bg-red-50/30 p-6 rounded-[2.5rem] border border-red-100 relative" style="overflow: visible !important;">
                                <label class="label-mini label-large mb-4 text-red-800 font-black tracking-tighter uppercase"><i class="fas fa-box-open mr-1"></i> 4. MUESTRAS DE PROMOCIÓN ENTREGADAS </label>
                                
                                <div class="form-group relative mb-4">
                                    <div class="relative">
                                        <input v-model="deliveredInput.titulo" type="text" class="form-input pr-10 font-bold border-red-100" placeholder="Buscar material promoción..." @input="searchBooks($event, 'delivered')" autocomplete="off">
                                        <i v-if="searchingDelivered" class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-red-400"></i>
                                    </div>
                                    <ul v-if="deliveredSuggestions.length" class="autocomplete-list shadow-2xl border border-slate-100">
                                        <li v-for="b in deliveredSuggestions" :key="b.id" @click="addMaterial(b, 'delivered')" class="text-[10px] font-black uppercase text-slate-700 hover:bg-red-50 p-3 transition-colors border-b last:border-0 border-slate-50">
                                            {{ b.titulo }}
                                        </li>
                                    </ul>
                                </div>
                                
                                <div v-if="selectedDeliveredBooks.length" class="table-modern-wrapper mt-6 overflow-hidden rounded-2xl border border-red-100 bg-white">
                                    <table class="w-full divide-y divide-red-50">
                                        <thead class="bg-red-900 text-white text-[9px] uppercase tracking-widest font-black">
                                            <tr>
                                                <th class="px-4 py-3 text-left">Libro</th>
                                                <th class="px-4 py-3 text-center w-32">Cantidad</th>
                                                <th class="px-4 py-3 w-16"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-red-50">
                                            <tr v-for="(item, idx) in selectedDeliveredBooks" :key="idx" class="hover:bg-red-50/20 transition-colors">
                                                <td class="table-cell">
                                                    <div class="text-[11px] font-black uppercase">{{ item.titulo }}</div>
                                                </td>
                                                <td class="table-cell text-center">
                                                    <input v-model.number="item.cantidad" type="number" min="1" class="input-table text-center" />
                                                </td>
                                                <td class="table-cell text-right">
                                                    <button type="button"  @click="selectedDeliveredBooks.splice(idx, 1)" class="text-red-300 btn-secondary hover:text-red-600 transition-colors"><i class="fas fa-trash-alt"></i>Quitar</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                     <!-- RESULTADO Y MOTIVO -->
                        <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            <div class="form-group mb-6">
                                <label class="label-large">5. RESULTADO Y COMENTARIOS DE LA SESIÓN</label>
                                <select v-model="form.visita.resultado_visita" class="form-input font-black uppercase tracking-widest text-slate-700 lbb" required>
                                    <option value="seguimiento">CONTINUAR SEGUIMIENTO</option>
                                    <option value="compra">DECISIÓN DE COMPRA</option>
                                    <option value="rechazo">RECHAZADO / CERRADO</option>
                                </select>
                            </div>
                            <div class="form-group mb-6">
                                <label class="label-style">COMENTARIOS Y ACUERDOS DE LA SESIÓN*</label>
                                <textarea v-model="form.visita.comentarios" class="form-input font-medium uppercase text-xs lbb" rows="4" required minlength="20"></textarea>
                            </div>
                        </div>
                        <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                            <div class="section-title label-large text-black">
                                <i class="fas fa-gift text-red-700"></i> 6. BENEFICIOS PARA EL CLIENTE
                            </div>
                            <div class="form-group mb-6">
                                <textarea v-model="form.plantel.beneficios_adicionales" class="form-input font-medium uppercase text-xs lbb" rows="4" required minlength="20" placeholder="ESPECIFIQUE LOS BENEFICIOS ACORDADOS CON EL PLANTEL..."></textarea>
                            </div>
                        </div>
                        <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                          
                                <!-- CAMPO DE PRÓXIMA ACCIÓN AGENDADA (NUEVO REQUISITO) -->
                            <div v-if="form.visita.resultado_visita === 'seguimiento'" class="form-group mb-6 p-6 bg-orange-50 rounded-[2.5rem] border-2 border-orange-100 shadow-inner animate-fade-in lbb">
                               <label class="label-large">7. PROXIMO COMPROMISO</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lbb">
                                    <div>
                                        <label class="text-[8px] font-black text-orange-600 uppercase mb-1 block">Fecha estimada</label>
                                        <input v-model="form.visita.proxima_visita" type="date" class="form-input border-orange-200 font-bold lbb" required :disabled="loading">
                                    </div>
                                    <div>
                                        <label class="text-[8px] font-black text-orange-600 uppercase mb-1 block">Objetivo</label>
                                        <select v-model="form.visita.proxima_accion" class="form-input border-orange-200 font-bold lbb" :disabled="loading">
                                            <option value="visita">Visita de Seguimiento</option>
                                            <option value="presentacion">Presentación Académica</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section shadow-premium border-t-8 border-t-slate-800 bg-white p-8 rounded-[2.5rem] border border-slate-100">
                         
                             <div class="form-group p-6 bg-red-50 rounded-[2.5rem] border-2 border-red-100 shadow-inner lbb">
                                 <label class="label-large">8. MOTIVO DE LA MODIFICACIÓN</label>
                                <textarea v-model="form.motivo_cambio" class="form-input border-red-200 font-bold uppercase text-xs lbb" rows="3" placeholder="EXPLIQUE POR QUÉ SE EDITA ESTE REGISTRO..." required minlength="10"></textarea>
                            </div>
                        </div>
                    </div>
                </div> 

                <!-- BOTONES FINALES -->
                <div class="mt-10 flex flex-col md:flex-row justify-end gap-4 border-t border-slate-100 pt-8 pb-20">
                    <button type="button" @click="$router.back()" class="btn-secondary px-10 py-4 uppercase tracking-widest text-xs" :disabled="loading">Cerrar sin Cambios</button>
                    <button type="submit" class="btn-primary px-20 py-4 shadow-xl transition-all active:scale-95" :disabled="loading || !form.motivo_cambio || form.motivo_cambio.length < 10 || isFormBlockedByDuplicates">
                        <i class="fas" :class="loading ? 'fa-spinner fa-spin mr-2' : 'fa-save mr-2'"></i> 
                        {{ loading ? 'Sincronizando...' : 'Guardar y Auditar' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- MODALES DE SISTEMA -->
        <Teleport to="body">
            <Transition name="modal-pop">
                <div v-if="showSuccess" class="modal-overlay-wrapper" @click.self="showSuccess = false">
                    <div class="modal-content-success animate-scale-in">
                        <div class="success-icon-wrapper shadow-lg shadow-green-100"><i class="fas fa-check"></i></div>
                        <h2 class="text-2xl font-black text-slate-800 mb-2 uppercase tracking-tighter">¡Expediente Actualizado!</h2>
                        <p class="text-sm text-slate-500 mb-8 font-medium px-4">Los cambios han sido guardados y registrados en la bitácora de auditoría.</p>
                        <button @click="$router.push('/visitas')" class="btn-primary w-full py-4 !bg-slate-900 border-none text-white font-black tracking-widest text-xs uppercase">Continuar</button>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <Teleport to="body">
            <Transition name="modal-pop">
                <div v-if="errorMessage" class="modal-overlay-wrapper" @click.self="errorMessage = null">
                    <div class="modal-content-success animate-scale-in">
                        <div class="error-icon
                        -wrapper shadow-lg shadow-red-100"><i class="fas fa-exclamation-triangle"></i></div>
                        <h2 class="text-2xl font-black text-red-700 mb-2 uppercase tracking-tighter">Error al Guardar</h2>
                        <p class="text-sm text-red-600 mb-8 font-medium px-4">{{ errorMessage }}</p>
                        <button @click="errorMessage = null" class="btn-primary w-full py-4 bg-red-600 border-none text-white font-black tracking-widest text-xs uppercase">Revisar Formulario</button>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- MODAL DE INTEGRIDAD (DUPLICADOS) -->
        <Teleport to="body">
            <Transition name="modal-pop">
                <div v-if="showDuplicateModal" class="modal-overlay-wrapper" @click.self="showDuplicateModal = false">
                    <div class=" modal-content-success        bg-white w-full max-w-md rounded-[3rem] shadow-2xl overflow-hidden border border-red-100 animate-scale-in">
                        <div class="bg-red-600 h-4 w-full"></div>
                        <div class="p-10 flex flex-col items-center">
                            <div class="bg-red-50 text-red-600 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-6 shadow-sm border-4 border-white ring-2 ring-red-50">
                                <i class="fas fa-exclamation-triangle text-3xl animate-pulse"></i>
                            </div>
                            <div class="bgcolor flex flex-col justify-content-center rounded-3 p-4 shadow-inner border border-danger mb-8">
                                <h4 class="mb-2 font-black uppercase tracking-tighter text-red-700 text-sm">Integridad de Datos</h4>
                                <p class="mb-0 font-bold uppercase text-[10px] text-red-600 leading-relaxed text-center">
                                    {{ activeDuplicateMessage }}
                                </p>
                            </div>
                            <button type="button" @click="showDuplicateModal = false" class="btn-primary w-full py-5 bg-black border-none text-white font-black uppercase tracking-widest rounded-2xl transition-transform hover:scale-105">Revisar formulario</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from '../axios';

const router = useRouter();
const route = useRoute();
const id = route.params.id;

const loading = ref(false);
const loadingInitial = ref(true);
const gettingLocation = ref(false);
const searchingInterest = ref(false);
const searchingDelivered = ref(false);
const showSuccess = ref(false);
const showDuplicateModal = ref(false);
const errorMessage = ref(null);

const visita = ref(null);
const estados = ref([]);
const nivelesCatalog = ref([]);
const allSeries = ref([]);

const selectedInterestBooks = ref([]);
const selectedDeliveredBooks = ref([]);
const interestInput = reactive({ titulo: '' });
const deliveredInput = reactive({ titulo: '' });
const interestSuggestions = ref([]);
const deliveredSuggestions = ref([]);
const selectedSerieIdA = ref('');

const fotoFile = ref(null);
const fotoPreview = ref(null);

// 1. Catálogo de cargos para validación de "Otro"
const cargosEstandar = [
    'Director/Coordinador', 
    'Subdirector', 
    'Jefe de Departamento', 
    'Profesor'
];

// Validación de unicidad global proactiva
const fieldValidation = reactive({ 
    name: { error: false, message: '' },
    rfc: { error: false, message: '' }, 
    email: { error: false, message: '' }, 
    telefono: { error: false, message: '' } 
});

const isFormBlockedByDuplicates = computed(() => {
    if (!visita.value?.es_primera_visita) return false;
    return fieldValidation.name.error || fieldValidation.rfc.error || fieldValidation.email.error || fieldValidation.telefono.error;
});

const activeDuplicateMessage = computed(() => {
    const active = Object.values(fieldValidation).find(f => f.error);
    return active ? active.message : '';
});

watch(isFormBlockedByDuplicates, (val) => {
    if (val) showDuplicateModal.value = true;
});

let bookTimer = null;

const form = reactive({
    plantel: { 
        name: '', 
        rfc: '', 
        niveles: [], 
        direccion: '', 
        estado_id: '', 
        telefono: '', 
        tel_oficina: '',
        extension: '',
        email: '', 
        director: '', 
        beneficios_adicionales: '',
        latitud: null, 
        longitud: null 
    },
    visita: { 
        fecha: '', 
        persona_entrevistada: '', 
        cargo: '', 
        cargo_especifico: '', // Campo para la opción "Otro"
        comentarios: '', 
        resultado_visita: 'seguimiento', 
        proxima_visita: '',
        proxima_accion: 'visita'
    },
    motivo_cambio: ''
});

const isLocked = computed(() => {
    if (!visita.value) return false;
    return !visita.value.es_primera_visita && visita.value.modificaciones_realizadas >= 1;
});

const fetchInitialData = async () => {
    loadingInitial.value = true;
    try {
        const [resEst, resNiv, resSer, resVis] = await Promise.all([
            axios.get('/estados'),
            axios.get('/search/niveles'),
            axios.get('/search/series'),
            axios.get(`/visitas/${id}`)
        ]);

        estados.value = resEst.data;
        nivelesCatalog.value = resNiv.data;
        allSeries.value = resSer.data;
        visita.value = resVis.data;

        // Hidratar Datos del Plantel
        form.plantel.name = (visita.value.nombre_plantel || visita.value.cliente?.name || '').toUpperCase();
        form.plantel.rfc = (visita.value.rfc_plantel || visita.value.cliente?.rfc || '').toUpperCase();
        form.plantel.direccion = (visita.value.direccion_plantel || visita.value.cliente?.direccion || '').toUpperCase();
        form.plantel.estado_id = visita.value.estado_id || visita.value.cliente?.estado_id || '';
        form.plantel.telefono = visita.value.telefono_plantel || visita.value.cliente?.telefono || '';
        form.plantel.tel_oficina = visita.value.cliente?.tel_oficina || '';
        form.plantel.extension = visita.value.cliente?.extension || '';
        form.plantel.foto_plantel = visita.value.cliente?.foto_plantel || null;
        if (visita.value?.cliente?.foto_plantel) {
            fotoPreview.value = `/storage/${visita.value.cliente.foto_plantel}`;
        } else {
            fotoPreview.value = null;
        }
        form.plantel.beneficios_adicionales = visita.value.cliente?.beneficios_adicionales || '';
        form.plantel.email = (visita.value.email_plantel || visita.value.cliente?.email || '').toLowerCase();
        form.plantel.director = (visita.value.director_plantel || visita.value.cliente?.contacto || '').toUpperCase();
        form.plantel.latitud = visita.value.latitud;
        form.plantel.longitud = visita.value.longitud;

        // Hidratar Niveles
        const nivelesRaw = visita.value.nivel_educativo_plantel || (visita.value.cliente && visita.value.cliente.nivel_educativo) || '';
        const nombresArr = nivelesRaw.split(',').map(n => n.trim().toLowerCase());
        form.plantel.niveles = nivelesCatalog.value.filter(niv => nombresArr.includes(niv.nombre.toLowerCase())).map(niv => niv.id);

        // Hidratar Visita
        form.visita.fecha = visita.value.fecha?.split('T')[0] || '';
        form.visita.persona_entrevistada = (visita.value.persona_entrevistada || '').toUpperCase();
        form.visita.comentarios = visita.value.comentarios || '';
        form.visita.resultado_visita = visita.value.resultado_visita || 'seguimiento';
        form.visita.proxima_visita = visita.value.proxima_visita_estimada ? visita.value.proxima_visita_estimada.split('T')[0] : '';
        form.visita.proxima_accion = visita.value.proxima_accion || 'visita';

        // 2. Lógica Unificada de Hidratación de Cargo
        const dbCargo = visita.value.cargo || '';
        const coincide = cargosEstandar.find(c => c.toUpperCase() === dbCargo.toUpperCase());

        if (coincide) {
            form.visita.cargo = coincide;
            form.visita.cargo_especifico = '';
        } else if (dbCargo !== '') {
            form.visita.cargo = 'Otro';
            form.visita.cargo_especifico = dbCargo;
        }

        // Hidratar Materiales
        const materiales = parseMateriales(visita.value.libros_interes);
        selectedInterestBooks.value = materiales.interes || [];
        selectedDeliveredBooks.value = materiales.entregado || [];

    } catch (e) {
        console.error("Error cargando expediente:", e);
        router.push('/visitas');
    } finally {
        loadingInitial.value = false;
    }
};

const validateUniqueness = async (field) => {
    if (!visita.value?.es_primera_visita) return;
    let val = '';
    let queryParam = field; 
    if (field === 'name') { val = form.plantel.name?.trim(); queryParam = 'nombre'; }
    else if (field === 'rfc') val = form.plantel.rfc?.trim().toUpperCase();
    else if (field === 'email') { val = form.plantel.email?.trim().toLowerCase(); queryParam = 'correo'; }
    else if (field === 'telefono') val = form.plantel.telefono?.trim();

    if (!val || val.length < 5) { 
        fieldValidation[field].error = false; 
        fieldValidation[field].message = '';
        return; 
    }

    try {
        const res = await axios.get('/search/receptores/check-rfc', { params: { [queryParam]: val } });
        if (res.data.status === 'conflict') {
            if (res.data.id && visita.value.cliente_id && res.data.cliente_id == visita.value.cliente_id) {
                fieldValidation[field].error = false;
                fieldValidation[field].message = '';
            } else {
                fieldValidation[field].error = true;
                fieldValidation[field].message = res.data.message;
            }
        } else {
            fieldValidation[field].error = false;
            fieldValidation[field].message = '';
        }
    } catch (e) { fieldValidation[field].error = false; }
};

const parseMateriales = (raw) => {
    if (!raw) return { interes: [], entregado: [] };
    if (typeof raw === 'object' && !Array.isArray(raw)) return raw;
    try { return JSON.parse(raw); } catch (e) { return { interes: [], entregado: [] }; }
};

const searchBooks = (event, type) => {
    const val = event.target.value;
    if (val.length < 3) return type === 'interest' ? interestSuggestions.value = [] : deliveredSuggestions.value = [];
    if (type === 'interest') searchingInterest.value = true; else searchingDelivered.value = true;
    if (bookTimer) clearTimeout(bookTimer);
    const serieId = type === 'interest' ? (selectedSerieIdA.value === 'otro' ? null : selectedSerieIdA.value) : null; 
    bookTimer = setTimeout(async () => {
        try {
            const res = await axios.get('search/libros', { params: { query: val, serie_id: serieId } });
            if (type === 'interest') interestSuggestions.value = res.data.filter(b => b.type !== 'promocion');
            else deliveredSuggestions.value = res.data.filter(b => b.type === 'promocion');
        } catch (e) { console.error(e); } finally { searchingInterest.value = false; searchingDelivered.value = false; }
    }, 400);
};

const addMaterial = (book, type) => {
    const serie = allSeries.value.find(s => s.id == book.serie_id);
    const serieNombre = serie ? (serie.nombre || serie.serie) : 'Sin Serie';
    if (type === 'interest') {
        if (!selectedInterestBooks.value.find(b => b.id === book.id)) {
            selectedInterestBooks.value.push({ 
                id: book.id, 
                titulo: book.titulo, 
                serie_nombre: serieNombre, 
                original_type: book.type, 
                tipo: book.type === 'digital' ? 'digital' : 'fisico',
                beneficio_tipo: 'Precio especial',
                beneficio_valor: 0
            });
        }
        interestInput.titulo = ''; interestSuggestions.value = [];
    } else {
        if (!selectedDeliveredBooks.value.find(b => b.id === book.id)) {
            selectedDeliveredBooks.value.push({ id: book.id, titulo: book.titulo, serie_nombre: serieNombre, cantidad: 1 });
        }
        deliveredInput.titulo = ''; deliveredSuggestions.value = [];
    }
};

const getLocation = () => {
    if (!navigator.geolocation) return;
    gettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
        (p) => { form.plantel.latitud = p.coords.latitude; form.plantel.longitud = p.coords.longitude; gettingLocation.value = false; },
        () => { gettingLocation.value = false; },
        { enableHighAccuracy: true }
    );
};

const handleFotoUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 4 * 1024 * 1024) {
        errorMessage.value = "LA IMAGEN EXPORTO UN PESO MAYOR AL LÍMITE PERMITIDO (4MB).";
        return;
    }
    fotoFile.value = file;
    fotoPreview.value = URL.createObjectURL(file);
};

const removeFoto = () => {
    // 1. Limpieza visual inmediata en pantalla
    fotoFile.value = null;
    fotoPreview.value = null;
    
    // 2. Limpieza del formulario reactivo local
    form.plantel.foto_plantel = null;
    if (visita.value?.cliente) {
        visita.value.cliente.foto_plantel = null;
    }

    // 3. LA CLAVE: Forzar a que el objeto del formulario lleve la bandera de eliminación para Laravel
    form.foto_plantel_eliminar = 'true';
};

const handleSubmit = async () => {
    errorMessage.value = null;
    if (isFormBlockedByDuplicates.value) return;

    if (visita.value.es_primera_visita && selectedInterestBooks.value.length === 0) {
        errorMessage.value = "PARA REGISTRAR UNA APERTURA SE REQUIERE AL MENOS UN MATERIAL DE INTERÉS.";
        return;
    }

    // Validar los campos de precio especial o descuento obligatorios en libros de interés antes de enviar
    const camposIncompletos = selectedInterestBooks.value.some(libro => !libro.beneficio_tipo || libro.beneficio_valor === undefined || libro.beneficio_valor === '');
    if (camposIncompletos) {
        errorMessage.value = "POR FAVOR, ESPECIFIQUE EL TIPO DE BENEFICIO Y EL VALOR EN TODOS LOS LIBROS DE INTERÉS.";
        return;
    }

    // Validar longitud mínima de Beneficios Adicionales
    if (!form.plantel.beneficios_adicionales || form.plantel.beneficios_adicionales.trim().length < 20) {
        errorMessage.value = "EL CAMPO DE 'BENEFICIOS ADICIONALES' ES OBLIGATORIO Y DEBE CONTENER AL MENOS 20 CARACTERES.";
        return;
    }

    loading.value = true;
    try {
        const nivelNombres = nivelesCatalog.value
            .filter(n => form.plantel.niveles.includes(n.id))
            .map(n => n.nombre)
            .join(', ');

        // 3. Lógica Unificada para enviar el cargo correcto
        const cargoFinal = form.visita.cargo === 'Otro' 
               ? form.visita.cargo_especifico.toUpperCase() 
               : form.visita.cargo.toUpperCase();

        const payload = {
            ...form.visita,
            cargo: cargoFinal,
            motivo_cambio: form.motivo_cambio,
            plantel: { 
                ...form.plantel, 
                niveles: nivelNombres,
                email: form.plantel.email.toLowerCase() 
            },
            libros_interes: {
                interes: selectedInterestBooks.value,
                entregado: selectedDeliveredBooks.value
            }
        };

        // Creamos un contenedor FormData para que viaje todo junto (Datos planos + Foto Binaria)
        const formData = new FormData();
        formData.append('_method', 'PUT'); // Truco nativo de Laravel para procesar PUT con archivos binarios
        formData.append('data', JSON.stringify(payload)); // Empacamos todo tu formulario plano intacto

        // Adjuntamos la fotografía o la bandera de eliminación según corresponda
        if (fotoFile.value) {
            formData.append('foto_plantel', fotoFile.value);
        } else if (form.plantel.foto_plantel === null) {
            formData.append('foto_plantel_eliminar', 'true');
        }

        // Realizamos el envío unificado al servidor
        const response = await axios.post(`/visitas/${id}`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        showSuccess.value = true;
    } catch (e) {
        errorMessage.value = e.response?.data?.message || "Fallo técnico en la sincronización.";
    } finally {
        loading.value = false;
    }
};

const handleSerieChange = (type) => { if (type === 'interest') { interestInput.titulo = ''; interestSuggestions.value = []; } };

onMounted(fetchInitialData);
</script>

<style scoped>
.label-style { @apply text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block; }
.label-mini { @apply text-[9px] uppercase font-black text-slate-400 mb-1 block tracking-widest; }
.shadow-premium { box-shadow: 0 20px 50px -20px rgba(0,0,0,0.08); }
.form-section { background: white; border: 1px solid #f1f5f9; border-radius: 2rem; }
.section-title { font-weight: 900; color: #a93339; margin-bottom: 25px; border-bottom: 2px solid #f8fafc; padding-bottom: 12px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 2px; }
.label-large { display: block; font-size: 0.79rem; font-weight: 900; text-transform: uppercase; color: #000000; margin-bottom: 6px; letter-spacing: 0.12em; opacity: 0.8; }

.form-input { width: 100%; padding: 14px 18px; border-radius: 16px; border: 2px solid #f1f5f9; font-weight: 700; color: #334155; background: #fafbfc; transition: all 0.2s; font-size: 0.9rem; }
.form-input:focus { border-color: #a93339; background: white; outline: none; }
.form-input:disabled { background-color: #f8fafc; color: #94a3b8; cursor: not-allowed; border-style: dashed; }

.btn-primary { background: linear-gradient(135deg, #e4989c 0%, #d46a8a 100%); color: white; border-radius: 20px; font-weight: 900; cursor: pointer; border: none; box-shadow: 0 10px 25px rgba(169, 51, 57, 0.2); transition: all 0.2s; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
.btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(169, 51, 57, 0.3); }

.btn-secondary-custom { @apply bg-white border-2 border-slate-200 py-3 px-8 rounded-2xl text-sm font-black transition-all hover:bg-slate-50 text-black; }

.modal-overlay-wrapper { position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; background-color: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); }
.modal-content-success { background: white; padding: 50px; border-radius: 50px; text-align: center; width: 90%; max-width: 400px; border: 1px solid #f1f5f9; }
.success-icon-wrapper { width: 85px; height: 85px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 30px; border: 5px solid white; }

.autocomplete-list { position: absolute; z-index: 1000; width: 100%; background: white; border-radius: 16px; max-height: 250px; overflow-y: auto; list-style: none; padding: 5px; margin-top: 5px; box-shadow: 0 15px 35px -10px rgba(0,0,0,0.1); }

.select-table { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 10px; font-weight: 900; color: #1e293b; padding: 6px 8px; text-transform: uppercase; width: 100%; }
.input-table { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 10px; font-weight: 900; color: #1e293b; padding: 6px 8px; text-transform: uppercase; width: 100%; }

.animate-scale-in { animation: scaleIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

.animate-fade-in { animation: fadeIn 0.4s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

select { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 1rem center; background-repeat: no-repeat; background-size: 1.25em 1.25em; padding-right: 2.25rem; appearance: none; }

.bgcolor { background: #fef2f2; border: 1px solid #fee2e2; padding: 16px; border-radius: 12px; }
.text-danger { color: #dc2626; }
.border-danger { border-color: #dc2626; }

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