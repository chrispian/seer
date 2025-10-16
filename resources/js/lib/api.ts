export async function apiRequest(url: string, options?: RequestInit) {
  const response = await fetch(url, {
    ...options,
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...options?.headers,
    },
  })

  if (!response.ok) {
    const error = await response.json().catch(() => ({ 
      message: response.statusText 
    }))
    throw new Error(error.message || error.error || 'Request failed')
  }

  return response.json()
}

export async function apiGet(url: string) {
  return apiRequest(url, { method: 'GET' })
}

export async function apiPost(url: string, data?: any) {
  return apiRequest(url, {
    method: 'POST',
    body: data ? JSON.stringify(data) : undefined,
  })
}

export async function apiPut(url: string, data?: any) {
  return apiRequest(url, {
    method: 'PUT',
    body: data ? JSON.stringify(data) : undefined,
  })
}

export async function apiDelete(url: string) {
  return apiRequest(url, { method: 'DELETE' })
}
