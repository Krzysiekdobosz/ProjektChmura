import api from './api'

export const blogService = {
  // Publiczne
  list:       (params) => api.get('/blog-posts', { params }),
  show:       (slug)   => api.get(`/blog-posts/${slug}`),
  categories: ()       => api.get('/blog-categories'),

  // Admin
  adminList:   (params) => api.get('/admin/blog-posts', { params }),
  adminShow:   (id)     => api.get(`/admin/blog-posts/${id}`),
  adminCreate: (data)   => api.post('/admin/blog-posts', data),
  adminUpdate: (id, data) => api.put(`/admin/blog-posts/${id}`, data),
  adminDelete: (id)     => api.delete(`/admin/blog-posts/${id}`),
}
