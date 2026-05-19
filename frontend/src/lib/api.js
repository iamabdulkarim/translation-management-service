import axios from 'axios'

const rawBaseUrl =
  import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

export const api = axios.create({
  baseURL: `${rawBaseUrl.replace(/\/$/, '')}/v1`,
  headers: {
    Accept: 'application/json',
  },
})

export function bearer(token) {
  return {
    Authorization: `Bearer ${token}`,
  }
}

export function compactParams(params) {
  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== '' && value !== null),
  )
}

export function apiErrorMessage(error) {
  const response = error?.response?.data

  if (response?.errors) {
    return Object.values(response.errors).flat().join(' ')
  }

  return response?.message || error?.message || 'Request failed'
}
