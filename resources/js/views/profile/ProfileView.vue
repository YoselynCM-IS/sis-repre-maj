<template>
  <div class="profile-root">

    <!-- ── Fondo decorativo ── -->
    <div class="bg-orbs" aria-hidden="true">
      <div class="orb orb-1"></div>
      <div class="orb orb-2"></div>
      <div class="orb orb-3"></div>
    </div>

    <!-- ── LOADER ── -->
    <Transition name="fade">
      <div v-if="loadingData" class="loader-screen">
        <div class="loader-ring"></div>
        <p class="loader-text">Sincronizando perfil...</p>
      </div>
    </Transition>

    <div v-if="!loadingData" class="profile-layout">

      <aside class="sidebar">

        <!-- Separador con etiqueta -->
        <div class="nav-label">Navegación</div>

        <!-- Items de navegación -->
        <nav class="sidebar-nav">
          <button
            v-for="item in navItems"
            :key="item.key"
            @click="activeSection = item.key"
            :class="['nav-item', { 'nav-item--active': activeSection === item.key }]"
          >
            <span class="nav-icon"><i :class="item.icon"></i></span>
            <span class="nav-label-text">{{ item.label }}</span>
            <span v-if="activeSection === item.key" class="nav-pip"></span>
          </button>
        </nav>

        <!-- Footer del sidebar -->
        <div class="sidebar-footer">
          <div class="sidebar-footer-inner">
            <i class="fas fa-shield-check text-emerald-400"></i>
            <span>Sesión verificada</span>
          </div>
        </div>

      </aside>

      <main class="content-area">

        <!-- Encabezado de sección -->
        <header class="content-header">
          <div class="content-header-left">
            <div class="section-eyebrow">Mi Perfil Profesional</div>
            <h1 class="section-title">{{ currentSection.label }}</h1>
          </div>
          <div class="header-actions">
            <div class="header-date">
              <i class="far fa-calendar"></i>
              {{ today }}
            </div>
          </div>
        </header>

        <!-- ─────────── SECCIÓN: DATOS PERSONALES ─────────── -->
        <Transition name="section-slide" mode="out-in">
          <section v-if="activeSection === 'personal'" key="personal" class="section-card">

            <div class="card-strip card-strip--crimson"></div>

            <div class="card-body">
              <div class="card-intro">
                <div class="card-intro-icon"><i class="fas fa-user-edit"></i></div>
                <div>
                  <h2 class="card-heading">Identidad y Ubicación</h2>
                  <p class="card-subheading">Información oficial para trámites y contacto</p>
                </div>
              </div>

              <form @submit.prevent="confirmUpdateProfile">
                <div class="fields-grid">

                  <div class="field-group field-full">
                    <label class="field-label">Nombre Completo (Real) <span class="req">*</span></label>
                    <div class="input-wrap">
                      <i class="fas fa-user input-icon"></i>
                      <input v-model="user.full_name" type="text" class="field-input" placeholder="Nombre oficial completo" required>
                    </div>
                    <p class="field-hint"><i class="fas fa-info-circle"></i> Use su nombre exacto como aparece en documentos oficiales.</p>
                  </div>

                  <div class="field-group">
                    <label class="field-label">Usuario de Acceso</label>
                    <div class="input-wrap">
                      <i class="fas fa-at input-icon"></i>
                      <input v-model="user.name" type="text" class="field-input field-readonly" readonly>
                    </div>
                  </div>

                  <div class="field-group">
                    <label class="field-label">RFC Personal</label>
                    <div class="input-wrap">
                      <i class="fas fa-id-card input-icon"></i>
                      <input v-model="user.rfc" type="text" class="field-input field-mono" placeholder="ABCD000000XXX">
                    </div>
                  </div>

                  <div class="field-group">
                    <label class="field-label">Puesto / Cargo</label>
                    <div class="input-wrap">
                      <i class="fas fa-briefcase input-icon"></i>
                      <input v-model="user.position" type="text" class="field-input field-readonly" readonly>
                    </div>
                  </div>

                  <div class="field-group">
                    <label class="field-label">Correo Electrónico <span class="req">*</span></label>
                    <div class="input-wrap">
                      <i class="fas fa-envelope input-icon"></i>
                      <input v-model="user.email" type="email" class="field-input" required>
                    </div>
                  </div>

                  <div class="field-group">
                    <label class="field-label">Teléfono Trabajo</label>
                    <div class="input-wrap">
                      <i class="fas fa-phone input-icon"></i>
                      <input v-model="user.phone" type="tel" class="field-input">
                    </div>
                  </div>

                  <div class="field-group">
                    <label class="field-label">Teléfono Personal</label>
                    <div class="input-wrap">
                      <i class="fas fa-mobile-alt input-icon"></i>
                      <input v-model="user.personal_phone" type="tel" class="field-input">
                    </div>
                  </div>

                  <div class="field-divider"></div>

                  <div class="field-group">
                    <label class="field-label">Estado / Región <span class="req">*</span></label>
                    <div class="input-wrap">
                      <i class="fas fa-map-marker-alt input-icon"></i>
                      <select v-model="user.state_id" class="field-input" required>
                        <option value="">Seleccione...</option>
                        <option v-for="e in estados" :key="e.id" :value="e.id">{{ e.estado }}</option>
                      </select>
                    </div>
                  </div>

                  <div class="field-group">
                    <label class="field-label">Ciudad</label>
                    <div class="input-wrap">
                      <i class="fas fa-city input-icon"></i>
                      <input v-model="user.city" type="text" class="field-input">
                    </div>
                  </div>

                  <div class="field-group field-full">
                    <label class="field-label">Dirección Completa</label>
                    <div class="input-wrap">
                      <i class="fas fa-home input-icon"></i>
                      <input v-model="user.address" type="text" class="field-input" placeholder="Calle, número, colonia...">
                    </div>
                  </div>

                </div>

                <div class="form-footer">
                  <button type="submit" class="btn-primary" :disabled="loading">
                    <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                    {{ loading ? 'Guardando...' : 'Actualizar Información' }}
                  </button>
                </div>
              </form>
            </div>
          </section>
        </Transition>

        <!-- ─────────── SECCIÓN: HERRAMIENTAS ─────────── -->
        <Transition name="section-slide" mode="out-in">
          <section v-if="activeSection === 'tools'" key="tools" class="section-card">
            <div class="card-strip card-strip--blue"></div>
            <div class="card-body">
              <div class="card-intro">
                <div class="card-intro-icon card-intro-icon--blue"><i class="fas fa-tools"></i></div>
                <div>
                  <h2 class="card-heading">Herramientas del Trabajo</h2>
                  <p class="card-subheading">Recursos y activos asignados a tu cuenta</p>
                </div>
              </div>

              <form @submit.prevent="confirmUpdateTools">

                <!-- Bloque: Vehículo -->
                <div class="tools-block">
                  <div class="tools-block-label"><i class="fas fa-car"></i> Vehículo y Movilidad</div>
                  <div class="fields-grid">
                    <div class="field-group">
                      <label class="field-label">Placas del Automóvil</label>
                      <div class="input-wrap">
                        <i class="fas fa-car input-icon"></i>
                        <input v-model="user.car_plates" type="text" class="field-input field-mono field-upper">
                      </div>
                    </div>
                    <div class="field-group">
                      <label class="field-label">Tag de Telepeaje</label>
                      <div class="input-wrap">
                        <i class="fas fa-tag input-icon"></i>
                        <input v-model="user.tag_number" type="text" class="field-input field-mono">
                      </div>
                    </div>
                    <div class="field-group field-full">
                      <label class="field-label">Póliza de Seguro</label>
                      <div class="input-wrap">
                        <i class="fas fa-file-contract input-icon"></i>
                        <input v-model="user.insurance_policy" type="text" class="field-input">
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Bloque: Equipo -->
                <div class="tools-block">
                  <div class="tools-block-label"><i class="fas fa-laptop"></i> Equipo Tecnológico</div>
                  <div class="fields-grid">
                    <div class="field-group">
                      <label class="field-label">Equipo Celular</label>
                      <div class="input-wrap">
                        <i class="fas fa-mobile-alt input-icon"></i>
                        <input v-model="user.phone_model" type="text" class="field-input">
                      </div>
                    </div>
                    <div class="field-group">
                      <label class="field-label">Tablet</label>
                      <div class="input-wrap">
                        <i class="fas fa-tablet-alt input-icon"></i>
                        <input v-model="user.tablet_model" type="text" class="field-input">
                      </div>
                    </div>
                    <div class="field-group">
                      <label class="field-label">Equipo de Cómputo</label>
                      <div class="input-wrap">
                        <i class="fas fa-laptop input-icon"></i>
                        <input v-model="user.computer_model" type="text" class="field-input">
                      </div>
                    </div>
                    <div class="field-group">
                      <label class="field-label">Tarjeta Empresarial</label>
                      <div class="input-wrap">
                        <i class="fas fa-credit-card input-icon"></i>
                        <input v-model="user.business_card" type="text" class="field-input field-mono">
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-footer">
                  <button type="submit" class="btn-primary btn-primary--blue" :disabled="loading">
                    <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                    {{ loading ? 'Guardando...' : 'Guardar Inventario' }}
                  </button>
                </div>
              </form>
            </div>
          </section>
        </Transition>

        <!-- ─────────── SECCIÓN: SEGURIDAD ─────────── -->
        <Transition name="section-slide" mode="out-in">
          <section v-if="activeSection === 'security'" key="security" class="section-card">
            <div class="card-strip card-strip--amber"></div>
            <div class="card-body">
              <div class="card-intro">
                <div class="card-intro-icon card-intro-icon--amber"><i class="fas fa-shield-alt"></i></div>
                <div>
                  <h2 class="card-heading">Seguridad de Acceso</h2>
                  <p class="card-subheading">Mantén tus credenciales seguras y actualizadas</p>
                </div>
              </div>

              <!-- Indicador de seguridad -->
              <div class="security-meter">
                <div class="security-meter-label">Nivel de seguridad</div>
                <div class="security-bars">
                  <div class="security-bar security-bar--filled"></div>
                  <div class="security-bar security-bar--filled"></div>
                  <div class="security-bar security-bar--filled"></div>
                  <div class="security-bar"></div>
                  <div class="security-bar"></div>
                </div>
                <span class="security-level-text">Medio</span>
              </div>

              <form @submit.prevent="confirmUpdatePassword" class="security-form">
                <div class="field-group">
                  <label class="field-label">Contraseña Actual</label>
                  <div class="input-wrap">
                    <i class="fas fa-lock input-icon"></i>
                    <input v-model="security.current_password" :type="showPwd[0] ? 'text' : 'password'" class="field-input" required>
                    <button type="button" class="pwd-toggle" @click="showPwd[0] = !showPwd[0]">
                      <i class="fas" :class="showPwd[0] ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                  </div>
                </div>

                <div class="fields-grid mt-6">
                  <div class="field-group">
                    <label class="field-label">Nueva Contraseña</label>
                    <div class="input-wrap">
                      <i class="fas fa-key input-icon"></i>
                      <input v-model="security.password" :type="showPwd[1] ? 'text' : 'password'" class="field-input" required>
                      <button type="button" class="pwd-toggle" @click="showPwd[1] = !showPwd[1]">
                        <i class="fas" :class="showPwd[1] ? 'fa-eye-slash' : 'fa-eye'"></i>
                      </button>
                    </div>
                  </div>
                  <div class="field-group">
                    <label class="field-label">Confirmar Nueva Contraseña</label>
                    <div class="input-wrap">
                      <i class="fas fa-key input-icon"></i>
                      <input v-model="security.password_confirmation" :type="showPwd[2] ? 'text' : 'password'" class="field-input" required>
                      <button type="button" class="pwd-toggle" @click="showPwd[2] = !showPwd[2]">
                        <i class="fas" :class="showPwd[2] ? 'fa-eye-slash' : 'fa-eye'"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Requisitos de contraseña -->
                <div class="pwd-requirements">
                  <p class="pwd-req" :class="{ 'pwd-req--met': security.password.length >= 8 }">
                    <i class="fas fa-check-circle"></i> Mínimo 8 caracteres
                  </p>
                  <p class="pwd-req" :class="{ 'pwd-req--met': /[A-Z]/.test(security.password) }">
                    <i class="fas fa-check-circle"></i> Al menos una mayúscula
                  </p>
                  <p class="pwd-req" :class="{ 'pwd-req--met': /\d/.test(security.password) }">
                    <i class="fas fa-check-circle"></i> Al menos un número
                  </p>
                </div>

                <div class="form-footer">
                  <button type="submit" class="btn-primary btn-primary--amber" :disabled="loading">
                    <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-key'"></i>
                    {{ loading ? 'Cambiando...' : 'Cambiar Credenciales' }}
                  </button>
                </div>
              </form>
            </div>
          </section>
        </Transition>

        <!-- ─────────── SECCIÓN: DELEGADOS ─────────── -->
        <Transition name="section-slide" mode="out-in">
          <section v-if="activeSection === 'delegates' && user.role === 'representante'" key="delegates" class="section-card">
            <div class="card-strip card-strip--purple"></div>
            <div class="card-body">

              <div class="delegates-header">
                <div class="card-intro">
                  <div class="card-intro-icon card-intro-icon--purple"><i class="fas fa-users-cog"></i></div>
                  <div>
                    <h2 class="card-heading">Gestionar Delegados</h2>
                    <p class="card-subheading">Cuentas autorizadas bajo tu supervisión</p>
                  </div>
                </div>
                <button v-if="!showAddDelegate" @click="showAddDelegate = true" class="btn-add">
                  <i class="fas fa-plus"></i> Nuevo Delegado
                </button>
              </div>

              <!-- Formulario agregar delegado -->
              <Transition name="fade">
                <div v-if="showAddDelegate" class="add-delegate-form">
                  <div class="add-delegate-form-inner mb-2">
                    <div class="field-group">
                      <label class="field-label">Nombre Completo</label>
                      <div class="input-wrapper">
                        <span class="input-icon"><i class="fas fa-id-card"></i></span>
                        <input 
                          v-model="newDelegate.full_name" 
                          type="text" 
                          placeholder="Ej. Juan Pérez López" 
                          class="field-input"
                          required
                        />
                      </div>
                    </div>
                    <div class="field-group">
                      <label class="field-label">Usuario</label>
                      <div class="input-wrap">
                        <i class="fas fa-user input-icon"></i>
                        <input v-model="newDelegate.username" type="text" class="field-input" placeholder="ej. juan.perez" style="text-transform: lowercase !important;">
                      </div>
                    </div>
                    <div class="field-group">
                      <label class="field-label">Contraseña</label>
                      <div class="input-wrap" style="position: relative; display: flex; align-items: center; overflow: visible !important;">
                        <i class="fas fa-lock input-icon"></i>
                        <input 
                          v-model="newDelegate.password" 
                          :type="showPassword ? 'text' : 'password'" 
                          class="field-input" 
                          placeholder="••••••••"
                          style="padding-right: 42px !important; width: 100%; text-transform: none !important;" 
                        >
                        <button 
                          type="button" 
                          @click="showPassword = !showPassword" 
                          style="position: absolute; right: 14px; background: none; border: none; cursor: pointer; color: #94a3b8; transition: color 0.2s; z-index: 10 !important; display: flex; align-items: center; justify-content: center; height: 100%;"
                          onmouseover="this.style.color='#64748b'"
                          onmouseout="this.style.color='#94a3b8'"
                        >
                          {{ showPassword ? 'Hide' : 'Show' }}
                        </button>
                      </div>
                    </div>
                      
                  </div>
                  <div class="add-delegate-form-inner">
                    <div class="add-delegate-actions">
                      <button @click="addDelegate" class="btn-primary btn-primary--purple btn-sm">
                        <i class="fas fa-user-plus"></i> Crear Cuenta
                      </button>
                      <button @click="showAddDelegate = false" class="btn-ghost btn-sm">
                        <i class="fas fa-times"></i> Cancelar
                      </button>
                    </div>
                  </div>
                </div>
              </Transition>

              <!-- Lista vacía -->
              <div v-if="delegates.length === 0" class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-user-shield"></i></div>
                <p class="empty-state-text">No hay cuentas de delegados autorizadas</p>
                <p class="empty-state-sub">Agrega un delegado usando el botón superior</p>
              </div>

              <!-- Tabla de delegados -->
              <div v-else class="delegates-table-wrap">
                <table class="delegates-table">
                  <thead>
                    <tr>
                      <th>Delegado</th>
                      <th>Rol</th>
                      <th class="text-center">Estado</th>
                      <th class="text-right">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="delegate in delegates" :key="delegate.id">
                      <td>
                        <div class="delegate-cell">
                          <div class="delegate-avatar">{{ delegate.name.charAt(0).toUpperCase() }}</div>
                          <div>
                            <p class="delegate-name" style="text-transform: none !important; font-weight: 600; margin-bottom: 2px;">
                              {{ delegate.user.full_name }}
                            </p>
                            <p class="delegate-id" style="text-transform: lowercase !important; font-size: 0.85em; color: #64748b; margin: 0;">
                              {{ delegate.name.toLowerCase() }}
                            </p>
                          </div>
                        </div>
                      </td>
                      <td><span class="role-badge">Delegado Autorizado</span></td>
                      <td class="text-center"><span class="status-dot"><i class="fas fa-circle"></i> Activo</span></td>
                      <td class="text-right">
                        <button @click="confirmRemoveDelegate(delegate)" class="btn-revoke">
                          <i class="fas fa-user-slash"></i> Revocar
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

            </div>
          </section>
        </Transition>

      </main>
    </div>

    <Teleport to="body">
      <Transition name="modal">
        <div v-if="modal.visible" class="modal-overlay" @click.self="closeModal">
          <div class="modal-box" :class="`modal-box--${modal.type}`">

            <!-- Éxito -->
            <div v-if="modal.type === 'success'" class="modal-success">
              <div class="modal-success-icon"><i class="fas fa-check"></i></div>
              <h3 class="modal-title">{{ modal.title }}</h3>
              <p class="modal-msg">{{ modal.message }}</p>
              <button @click="closeModal" class="btn-primary mt-6">Continuar</button>
            </div>

            <!-- Confirmación / Error / Warning -->
            <div v-else class="modal-confirm">
              <div class="modal-confirm-icon" :class="`modal-confirm-icon--${modal.type}`">
                <i class="fas" :class="modal.type === 'danger' ? 'fa-exclamation-triangle' : modal.type === 'warning' ? 'fa-exclamation-circle' : 'fa-info-circle'"></i>
              </div>
              <h3 class="modal-title">{{ modal.title }}</h3>
              <p class="modal-msg">{{ modal.message }}</p>
              <div class="modal-actions">
                <button v-if="modal.confirm" @click="closeModal" class="btn-ghost">Cancelar</button>
                <button @click="handleModalConfirm" class="btn-primary" :class="modal.type === 'danger' ? 'btn-primary--danger' : ''">
                  {{ modal.confirm ? 'Confirmar' : 'Aceptar' }}
                </button>
              </div>
            </div>

          </div>
        </div>
      </Transition>
    </Teleport>

    <Transition name="toast">
      <div v-if="toast.visible" class="toast">
        <i class="fas fa-check-circle toast-icon"></i>
        <span>{{ toast.message }}</span>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import axios from '../../axios'

const loading = ref(false)
const loadingData = ref(true)
const activeSection = ref('personal')
const showAddDelegate = ref(false)
const showPwd = ref([false, false, false])
const showPassword = ref(false)

// Cambiamos el array fijo por uno computado que filtra según el rol
const navItems = computed(() => {
  const items = [
    { key: 'personal', label: 'Datos Personales', icon: 'fas fa-user-edit' },
    { key: 'tools', label: 'Herramientas de Trabajo', icon: 'fas fa-tools' },
    { key: 'security', label: 'Seguridad', icon: 'fas fa-shield-alt' },
  ];

  // Solo agregamos la opción si el usuario es representante
  if (user.value.role === 'representante') {
    items.push({ key: 'delegates', label: 'Gestionar Delegados', icon: 'fas fa-users-cog' });
  }

  return items;
});

const user = ref({
  name: '', full_name: '', rfc: '', email: '', phone: '',
  personal_phone: '', position: '', employee_id: '', state_id: '',
  city: '', address: '', car_plates: '', tag_number: '',
  insurance_policy: '', phone_model: '', tablet_model: '',
  computer_model: '', business_card: '',
})
const estados = ref([])
const delegates = ref([])
const security = reactive({ current_password: '', password: '', password_confirmation: '' })
const newDelegate = reactive({ full_name: '', username: '', password: '' })

const modal = reactive({ visible: false, title: '', message: '', type: 'info', confirm: null })
const toast = reactive({ visible: false, message: '' })

const initials = computed(() => {
  const name = user.value.full_name || user.value.name || ''
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase() || 'U'
})

const currentSection = computed(() => navItems.value.find(n => n.key === activeSection.value) || navItems.value[0])

const today = computed(() =>
  new Date().toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })
)

/* ── Toast ── */
const showToast = (msg) => {
  toast.message = msg; toast.visible = true
  setTimeout(() => toast.visible = false, 3500)
}

/* ── Modal ── */
const openModal = (title, message, type = 'info', confirmCb = null) => {
  Object.assign(modal, { visible: true, title, message, type, confirm: confirmCb })
}
const closeModal = () => { modal.visible = false }
const handleModalConfirm = () => { if (modal.confirm) modal.confirm(); else closeModal() }

/* ── Fetch ── */
const fetchInitialData = async () => {
  loadingData.value = true
  try {
    const [estadosRes, profileRes] = await Promise.all([
      axios.get('profile/estados'),
      axios.get('profile/myprofile')
    ])
    estados.value = estadosRes.data
    const d = profileRes.data.user || profileRes.data
    user.value = {
      name: d.name || '', role: d.role || '', full_name: d.full_name || '', email: d.email || '',
      rfc: d.rfc || '', phone: d.phone || '', personal_phone: d.personal_phone || '',
      position: d.position || '', employee_id: d.employee_id || '', state_id: d.state_id || '',
      city: d.city || '', address: d.address || '', car_plates: d.car_plates || '',
      tag_number: d.tag_number || '', insurance_policy: d.insurance_policy || '',
      phone_model: d.phone_model || '', tablet_model: d.tablet_model || '',
      computer_model: d.computer_model || '', business_card: d.business_card || '',
    }
    delegates.value = d.delegates || []
  } catch {
    openModal('Error de Sincronización', 'No se pudo recuperar el perfil.', 'danger')
  } finally {
    loadingData.value = false
  }
}

/* ── Acciones ── */
const confirmUpdateProfile = () =>
  openModal('Confirmar Actualización', '¿Guardar los cambios de identidad y ubicación?', 'info', updateProfile)

const updateProfile = async () => {
  loading.value = true; closeModal()
  try {
    await axios.put('profile', {
      full_name: user.value.full_name, email: user.value.email, rfc: user.value.rfc,
      phone: user.value.phone, personal_phone: user.value.personal_phone,
      state_id: user.value.state_id || null, city: user.value.city, address: user.value.address,
    })
    openModal('¡Perfil Actualizado!', 'Tu información se guardó con éxito.', 'success')
    await fetchInitialData()
  } catch (e) {
    if (e.response?.status === 422) {
      openModal('Validación', Object.values(e.response.data.errors)[0][0], 'warning')
    } else {
      openModal('Error', 'El servidor rechazó la solicitud.', 'danger')
    }
  } finally { loading.value = false }
}

const confirmUpdateTools = () =>
  openModal('Sincronizar Inventario', '¿Los datos de herramientas son correctos?', 'info', updateTools)

const updateTools = async () => {
  loading.value = true; closeModal()
  try {
    await axios.put('profile/tools', {
      car_plates: user.value.car_plates, tag_number: user.value.tag_number,
      insurance_policy: user.value.insurance_policy, phone_model: user.value.phone_model,
      tablet_model: user.value.tablet_model, computer_model: user.value.computer_model,
      business_card: user.value.business_card,
    })
    openModal('¡Inventario Guardado!', 'Los activos se actualizaron correctamente.', 'success')
    await fetchInitialData()
  } catch { openModal('Error', 'No se pudo guardar el inventario.', 'danger') }
  finally { loading.value = false }
}

const confirmUpdatePassword = () => {
  if (!security.current_password || !security.password)
    return openModal('Campos Requeridos', 'Completa todos los campos de contraseña.', 'warning')
  openModal('Cambio de Credenciales', '¿Confirmas el cambio de contraseña? Deberás usarla en el próximo login.', 'warning', updatePassword)
}

const updatePassword = async () => {
  if (security.password !== security.password_confirmation)
    return openModal('Error', 'Las contraseñas no coinciden.', 'warning')
  loading.value = true; closeModal()
  try {
    await axios.put('profile/password', security)
    openModal('¡Contraseña Cambiada!', 'Tus credenciales fueron actualizadas.', 'success')
    Object.assign(security, { current_password: '', password: '', password_confirmation: '' })
  } catch { openModal('Error', 'La contraseña actual es incorrecta.', 'danger') }
  finally { loading.value = false }
}

const addDelegate = async () => {
  if (!newDelegate.username || !newDelegate.password || !newDelegate.full_name)
    return openModal('Datos Incompletos', 'Escribe nombre, usuario y contraseña.', 'warning')
  loading.value = true
  try {
    const res = await axios.post('profile/delegates', newDelegate)
    delegates.value.push(res.data.delegate)
    newDelegate.username = ''; newDelegate.password = ''; newDelegate.full_name = '';
    showAddDelegate.value = false
    openModal('¡Delegado Creado!', 'La cuenta fue vinculada con éxito.', 'success')
  } catch { openModal('Error', 'No se pudo crear la cuenta delegada.', 'danger') }
  finally { loading.value = false }
}

const confirmRemoveDelegate = (d) =>
  openModal('¿Revocar Acceso?', `Se eliminará el acceso de "${d.name}". ¿Continuar?`, 'danger', () => removeDelegate(d.id))

const removeDelegate = async (id) => {
  closeModal()
  try {
    await axios.delete(`profile/delegates/${id}`)
    delegates.value = delegates.value.filter(d => d.id !== id)
    showToast('Acceso revocado correctamente')
  } catch { openModal('Error', 'No se pudo revocar el acceso.', 'danger') }
}

// OBSERVADOR PARA FORZAR EL NOMBRE DE USUARIO EN MINÚSCULAS Y SIN ESPACIOS
watch(() => newDelegate.username, (newValue) => {
  if (newValue) {
    newDelegate.username = newValue.toLowerCase().replace(/\s+/g, '');
  }
})

onMounted(fetchInitialData)
</script>

<style scoped>
/* ── Root ── */
.profile-root {
  min-height: 100vh;
  background:  hsl(357, 54%, 43%, 10%); 
  font-family: 'DM Sans', 'Outfit', system-ui, sans-serif;
  position: relative;
  overflow: hidden;
}

/* ── Orbs de fondo ── */
.bg-orbs { position: fixed; inset: 0; pointer-events: none; z-index: 0; }
.orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.06; }
.orb-1 { width: 500px; height: 500px; background: #c0152b; top: -150px; right: -100px; }
.orb-2 { width: 400px; height: 400px; background: #1a56db; bottom: -100px; left: -100px; }
.orb-3 { width: 300px; height: 300px; background: #7c3aed; top: 50%; left: 40%; }

/* ── Loader ── */
.loader-screen {
  position: fixed; inset: 0; z-index: 100;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  background: #f4f6f8;
}
.loader-ring {
  width: 48px; height: 48px;
  border: 3px solid #f1f5f9;
  border-top-color: var(--crimson);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
.loader-text { margin-top: 16px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .12em; color: #94a3b8; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Layout ── */
.profile-layout {
  position: relative; z-index: 1;
  max-width: 1280px; margin: 0 auto;
  padding: 40px 24px;
  display: flex; gap: 28px;
  min-height: 100vh;
}

.sidebar {
  width: 280px; flex-shrink: 0;
  background: white;
  border-radius: 24px;
  border: 1px solid #eef2f7;
  box-shadow: 0 4px 24px rgba(0,0,0,0.05);
  display: flex; flex-direction: column;
  position: sticky; top: 24px; align-self: flex-start;
  overflow: hidden;
}

/* Avatar / Identidad */
.identity-block {
  padding: 28px 24px 20px;
  display: flex; align-items: center; gap: 14px;
  background: linear-gradient(135deg, #fff 60%, #fef1f2);
  border-bottom: 1px solid #f5f7fa;
}
.avatar-ring {
  position: relative; width: 54px; height: 54px; flex-shrink: 0;
}
.avatar-inner {
  width: 100%; height: 100%;
  background: linear-gradient(135deg, var(--crimson), #881337);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 12px rgba(192,21,43,0.3);
}
.avatar-initials { color: white; font-size: 1.1rem; font-weight: 900; letter-spacing: -0.02em; }
.avatar-status {
  position: absolute; bottom: 2px; right: 2px;
  width: 12px; height: 12px; background: #22c55e;
  border-radius: 50%; border: 2px solid white;
}
.identity-name { font-size: 0.88rem; font-weight: 800; color: var(--slate-900); line-height: 1.2; }
.identity-role { font-size: 0.65rem; text-transform: uppercase; font-weight: 700; color: var(--crimson); letter-spacing: .06em; }

/* Nav label */
.nav-label {
  font-size: 0.58rem; font-weight: 900; text-transform: uppercase;
  letter-spacing: .14em; color: #b0bec5;
  padding: 16px 24px 8px;
}

/* Nav items */
.sidebar-nav { padding: 0 12px 12px; display: flex; flex-direction: column; gap: 4px; }
.nav-item {
  width: 100%; display: flex; align-items: center; gap: 12px;
  padding: 11px 16px; border-radius: 14px;
  font-size: 0.83rem; font-weight: 700; color: var(--slate-500);
  background: none; border: none; text-align: left; cursor: pointer;
  transition: all 0.18s ease; position: relative;
}
.nav-item:hover { background: #f8fafc; color: var(--slate-700); }
.nav-item--active { background: #fef1f2 !important; color: var(--crimson) !important; }
.nav-icon { width: 30px; height: 30px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; transition: background 0.18s; }
.nav-item--active .nav-icon { background: rgba(192,21,43,0.1); color: var(--crimson); }
.nav-label-text { flex: 1; }
.nav-pip { width: 6px; height: 6px; background: var(--crimson); border-radius: 50%; }

/* Footer sidebar */
.sidebar-footer { margin-top: auto; padding: 16px 20px; border-top: 1px solid #f1f5f9; }
.sidebar-footer-inner { display: flex; align-items: center; gap: 8px; font-size: 0.7rem; font-weight: 700; color: #94a3b8; }

.content-area { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 24px; }

/* Header */
.content-header {
  display: flex; justify-content: space-between; align-items: flex-start;
  padding: 4px 0 8px;
}
.section-eyebrow { font-size: 0.62rem; text-transform: uppercase; font-weight: 800; letter-spacing: .1em; color: var(--crimson); margin-bottom: 4px; }
.section-title { font-size: 1.6rem; font-weight: 900; color: var(--slate-900); letter-spacing: -0.02em; line-height: 1; }
.header-date { font-size: 0.75rem; font-weight: 700; color: var(--slate-500); background: white; border: 1px solid #eef2f7; padding: 8px 14px; border-radius: 10px; display: flex; align-items: center; gap: 6px; }

/* ── Section Card ── */
.section-card {
  background: white;
  border-radius: 24px;
  border: 1px solid #eef2f7;
  box-shadow: 0 4px 24px rgba(0,0,0,0.04);
  overflow: hidden;
}
.card-strip { height: 4px; }
.card-strip--crimson { background: linear-gradient(90deg, var(--crimson), #f43f5e); }
.card-strip--blue    { background: linear-gradient(90deg, var(--blue), #60a5fa); }
.card-strip--amber   { background: linear-gradient(90deg, var(--amber), #fbbf24); }
.card-strip--purple  { background: linear-gradient(90deg, var(--purple), #a78bfa); }

.card-body { padding: 32px 36px 36px; }

/* Card Intro */
.card-intro { display: flex; align-items: center; gap: 16px; margin-bottom: 32px; }
.card-intro-icon {
  width: 48px; height: 48px; border-radius: 14px;
  background: var(--crimson-light); color: var(--crimson);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0;
}
.card-intro-icon--blue   { background: var(--blue-light);   color: var(--blue); }
.card-intro-icon--amber  { background: var(--amber-light);  color: var(--amber); }
.card-intro-icon--purple { background: var(--purple-light); color: var(--purple); }
.card-heading { font-size: 1.1rem; font-weight: 900; color: var(--slate-900); }
.card-subheading { font-size: 0.72rem; color: var(--slate-500); margin-top: 2px; font-weight: 500; }

/* ── Fields ── */
.fields-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px 24px; }
.field-group { display: flex; flex-direction: column; gap: 7px; }
.field-full { grid-column: 1 / -1; }
.field-divider { grid-column: 1 / -1; height: 1px; background: #f1f5f9; }

.field-label { font-size: 0.62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #94a3b8; }
.req { color: var(--crimson); }

.input-wrap { position: relative; display: flex; align-items: center; }
.input-icon { position: absolute; left: 14px; color: #b0bec5; font-size: 0.8rem; pointer-events: none; }

.field-input {
  width: 100%; padding: 12px 16px 12px 38px;
  border: 2px solid #eef2f7; border-radius: 12px;
  font-size: 0.88rem; font-weight: 600; color: var(--slate-700);
  background: #fdfdfe;
  transition: all 0.18s ease;
  appearance: none;
}
.field-input:focus { border-color: var(--crimson); background: white; outline: none; box-shadow: 0 0 0 4px rgba(192,21,43,0.06); }
.field-readonly { background: #f8fafc !important; color: #b0bec5 !important; cursor: not-allowed; }
.field-mono { font-family: 'DM Mono', 'Courier New', monospace; letter-spacing: .05em; }
.field-upper { text-transform: uppercase; }
.field-hint { font-size: 0.65rem; color: #e87b8b; font-weight: 600; display: flex; align-items: center; gap: 4px; }

/* Toggle contraseña */
.pwd-toggle { position: absolute; right: 12px; background: none; border: none; cursor: pointer; color: #b0bec5; font-size: 0.8rem; transition: color 0.15s; }
.pwd-toggle:hover { color: var(--slate-700); }

/* Password requirements */
.pwd-requirements { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 16px; padding: 14px 18px; background: #f8fafc; border-radius: 12px; }
.pwd-req { font-size: 0.68rem; font-weight: 700; color: #b0bec5; display: flex; align-items: center; gap: 6px; transition: color 0.2s; }
.pwd-req--met { color: #22c55e; }

/* Security meter */
.security-meter { display: flex; align-items: center; gap: 12px; padding: 14px 18px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px; margin-bottom: 24px; }
.security-meter-label { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #92400e; flex-shrink: 0; }
.security-bars { display: flex; gap: 4px; }
.security-bar { width: 24px; height: 6px; border-radius: 3px; background: #f3f4f6; }
.security-bar--filled { background: var(--amber); }
.security-level-text { font-size: 0.7rem; font-weight: 800; color: var(--amber); }

/* Tools blocks */
.tools-block { margin-bottom: 28px; }
.tools-block-label { font-size: 0.65rem; font-weight: 900; text-transform: uppercase; letter-spacing: .1em; color: var(--blue); display: flex; align-items: center; gap: 8px; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid #e8f0fe; }

/* Form footer */
.form-footer { display: flex; justify-content: flex-end; padding-top: 24px; border-top: 1px solid #f1f5f9; margin-top: 28px; }

/* ── Delegates ── */
.delegates-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.add-delegate-form { background: #fef8f8; border: 1px dashed #fca5a5; border-radius: 16px; padding: 24px; margin-bottom: 24px; }
.add-delegate-form-inner { display: grid; grid-template-columns: 1fr 1fr auto; gap: 16px; align-items: end; }
.add-delegate-actions { display: flex; gap: 8px; }

/* Tabla delegados */
.delegates-table-wrap { overflow: hidden; border: 1px solid #f1f5f9; border-radius: 16px; }
.delegates-table { width: 100%; border-collapse: collapse; }
.delegates-table thead tr { background: #f8fafc; }
.delegates-table th { padding: 12px 20px; font-size: 0.62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #94a3b8; text-align: left; }
.delegates-table tbody tr { border-top: 1px solid #f8fafc; transition: background 0.15s; }
.delegates-table tbody tr:hover { background: #fafbfc; }
.delegates-table td { padding: 14px 20px; vertical-align: middle; }
.delegate-cell { display: flex; align-items: center; gap: 12px; }
.delegate-avatar { width: 36px; height: 36px; border-radius: 10px; background: #f1f5f9; color: var(--slate-700); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 900; flex-shrink: 0; }
.delegate-name { font-size: 0.85rem; font-weight: 800; color: var(--slate-700); }
.delegate-id { font-size: 0.62rem; color: #94a3b8; font-weight: 700; margin-top: 2px; }
.role-badge { font-size: 0.62rem; font-weight: 800; background: var(--purple-light); color: var(--purple); padding: 4px 10px; border-radius: 8px; text-transform: uppercase; letter-spacing: .06em; }
.status-dot { font-size: 0.68rem; font-weight: 800; color: #166534; display: inline-flex; align-items: center; gap: 5px; }
.status-dot i { font-size: 0.45rem; color: #22c55e; }

/* Empty state */
.empty-state { text-align: center; padding: 60px 20px; border: 2px dashed #e2e8f0; border-radius: 16px; }
.empty-state-icon { font-size: 2.5rem; color: #e2e8f0; margin-bottom: 12px; }
.empty-state-text { font-size: 0.88rem; font-weight: 700; color: #94a3b8; }
.empty-state-sub { font-size: 0.72rem; color: #b0bec5; margin-top: 4px; }

/* ── Buttons ── */
.btn-primary {
  display: inline-flex; align-items: center; gap: 8px;
  background: linear-gradient(135deg, var(--crimson), #9f1239);
  color: white; padding: 13px 28px; border-radius: 13px;
  font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em;
  border: none; cursor: pointer;
  box-shadow: 0 4px 16px rgba(192,21,43,0.25);
  transition: all 0.18s ease;
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(192,21,43,0.35); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.btn-primary--blue   { background: linear-gradient(135deg, var(--blue), #1e40af); box-shadow: 0 4px 16px rgba(26,86,219,0.25); }
.btn-primary--blue:hover { box-shadow: 0 8px 24px rgba(26,86,219,0.35); }
.btn-primary--amber  { background: linear-gradient(135deg, var(--amber), #92400e); box-shadow: 0 4px 16px rgba(217,119,6,0.25); }
.btn-primary--amber:hover { box-shadow: 0 8px 24px rgba(217,119,6,0.35); }
.btn-primary--purple { background: linear-gradient(135deg, var(--purple), #5b21b6); box-shadow: 0 4px 16px rgba(124,58,237,0.25); }
.btn-primary--danger { background: linear-gradient(135deg, #dc2626, #991b1b); box-shadow: 0 4px 16px rgba(220,38,38,0.3); }

.btn-sm { padding: 10px 18px; font-size: 0.72rem; border-radius: 10px; }

.btn-ghost {
  display: inline-flex; align-items: center; gap: 8px;
  background: none; border: 2px solid #e2e8f0; color: var(--slate-500);
  padding: 11px 20px; border-radius: 13px; font-size: 0.78rem; font-weight: 700;
  cursor: pointer; transition: all 0.15s;
}
.btn-ghost:hover { border-color: #b0bec5; color: var(--slate-700); background: #f8fafc; }

.btn-add {
  display: inline-flex; align-items: center; gap: 8px;
  background: var(--purple-light); color: var(--purple);
  border: 1px solid #ddd6fe; padding: 10px 18px; border-radius: 12px;
  font-size: 0.75rem; font-weight: 800; cursor: pointer; transition: all 0.15s;
}
.btn-add:hover { background: #ede9fe; }

.btn-revoke {
  display: inline-flex; align-items: center; gap: 6px;
  background: none; border: none; color: #b0bec5;
  font-size: 0.72rem; font-weight: 800; cursor: pointer; padding: 6px 10px; border-radius: 8px;
  text-transform: uppercase; letter-spacing: .06em; transition: all 0.15s;
}
.btn-revoke:hover { color: #dc2626; background: #fef2f2; }

/* ── Modal ── */
.modal-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(15,23,42,0.75); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center; padding: 24px;
}
.modal-box {
  background: white; border-radius: 28px; padding: 40px; width: 100%; max-width: 420px;
  box-shadow: 0 24px 60px rgba(0,0,0,0.3); border: 1px solid #f1f5f9;
}
.modal-success { text-align: center; }
.modal-success-icon {
  width: 72px; height: 72px; border-radius: 50%;
  background: #dcfce7; color: #166534;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.8rem; margin: 0 auto 20px;
  box-shadow: 0 0 0 8px rgba(34,197,94,0.1);
}
.modal-confirm { text-align: center; }
.modal-confirm-icon {
  width: 64px; height: 64px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; margin: 0 auto 20px;
}
.modal-confirm-icon--danger { background: #fef2f2; color: var(--crimson); box-shadow: 0 0 0 8px rgba(192,21,43,0.06); }
.modal-confirm-icon--warning { background: #fffbeb; color: var(--amber); box-shadow: 0 0 0 8px rgba(217,119,6,0.06); }
.modal-confirm-icon--info { background: #eff6ff; color: var(--blue); box-shadow: 0 0 0 8px rgba(26,86,219,0.06); }
.modal-title { font-size: 1.2rem; font-weight: 900; color: var(--slate-900); margin-bottom: 8px; }
.modal-msg { font-size: 0.83rem; color: var(--slate-500); line-height: 1.5; }
.modal-actions { display: flex; justify-content: center; gap: 10px; margin-top: 28px; }

/* ── Toast ── */
.toast {
  position: fixed; bottom: 32px; left: 50%; transform: translateX(-50%);
  background: var(--slate-900); color: white;
  padding: 14px 24px; border-radius: 999px;
  display: flex; align-items: center; gap: 10px;
  font-size: 0.83rem; font-weight: 700;
  box-shadow: 0 8px 24px rgba(0,0,0,0.25);
  z-index: 10000; white-space: nowrap;
}
.toast-icon { color: #4ade80; font-size: 1rem; }

/* ── Transitions ── */
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.section-slide-enter-active, .section-slide-leave-active { transition: all 0.22s ease; }
.section-slide-enter-from { opacity: 0; transform: translateY(12px); }
.section-slide-leave-to { opacity: 0; transform: translateY(-6px); }

.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from .modal-box, .modal-leave-to .modal-box { transform: scale(0.95) translateY(8px); }

.toast-enter-active, .toast-leave-active { transition: all 0.4s cubic-bezier(0.68,-0.55,0.27,1.55); }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(-50%) translateY(20px); }

/* ── Responsive ── */
@media (max-width: 1024px) {
  .profile-layout { flex-direction: column; padding: 20px 16px; }
  .sidebar { width: 100%; position: static; }
  .sidebar-nav { flex-direction: row; flex-wrap: wrap; }
  .nav-item { flex: 1; min-width: 140px; justify-content: center; }
  .nav-pip { display: none; }
  .fields-grid { grid-template-columns: 1fr; }
  .field-full { grid-column: auto; }
  .add-delegate-form-inner { grid-template-columns: 1fr; }
}

@media (max-width: 640px) {
  .card-body { padding: 20px; }
  .content-header { flex-direction: column; gap: 10px; }
  .delegates-header { flex-direction: column; gap: 12px; align-items: flex-start; }
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