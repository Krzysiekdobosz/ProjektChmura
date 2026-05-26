import api from './api'

export const propertyService = {
  // Publiczne
  list:    (params)       => api.get('/properties', { params }),
  get:     (slug)         => api.get(`/properties/${slug}`),

  // Właściciel
  ownerList:    (params)  => api.get('/owner/properties', { params }),
  ownerGet:     (id)      => api.get(`/owner/properties/${id}`),
  ownerCreate:  (data)    => api.post('/owner/properties', data),
  ownerUpdate:  (id, data)=> api.put(`/owner/properties/${id}`, data),
  ownerDelete:  (id)      => api.delete(`/owner/properties/${id}`),
  ownerPublish: (id)      => api.post(`/owner/properties/${id}/publish`),

  // Zdjęcia
  uploadImages: (id, form)=> api.post(`/owner/properties/${id}/images`, form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }),
  setMainImage: (id, imgId) => api.patch(`/owner/properties/${id}/images/${imgId}/main`),
  deleteImage:  (id, imgId) => api.delete(`/owner/properties/${id}/images/${imgId}`),
}
