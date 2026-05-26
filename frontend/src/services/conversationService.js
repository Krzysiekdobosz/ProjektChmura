import api from './api'

export const conversationService = {
  list:      (params) => api.get('/conversations', { params }),
  show:      (id)     => api.get(`/conversations/${id}`),
  start:     (data)   => api.post('/conversations', data),
  send:      (id, body) => api.post(`/conversations/${id}/messages`, { body }),
  markRead:  (id)     => api.patch(`/conversations/${id}/messages/read`),
}
