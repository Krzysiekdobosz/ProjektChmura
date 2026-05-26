import api from './api'

export const favoriteService = {
  list:   (params) => api.get('/favorites', { params }),
  add:    (propertyId) => api.post('/favorites', { property_id: propertyId }),
  remove: (propertyId) => api.delete(`/favorites/${propertyId}`),
}
