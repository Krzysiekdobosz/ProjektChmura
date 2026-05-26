import api from './api'

export const viewingService = {
  // Użytkownik
  list:   (params) => api.get('/property-viewings', { params }),
  book:   (data)   => api.post('/property-viewings', data),
  cancel: (id)     => api.delete(`/property-viewings/${id}`),

  // Właściciel
  ownerList:         (params) => api.get('/owner/viewings', { params }),
  ownerUpdateStatus: (id, status, ownerNote) => api.patch(`/owner/viewings/${id}/status`, { status, owner_note: ownerNote }),
}
