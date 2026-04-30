"use strict";

window.AutoSavUsers = (() => {
  const csrfToken = () => (
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    || window.CSRF_TOKEN
    || ''
  );

  async function request(url, options = {}) {
    const response = await fetch(url, {
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfToken(),
        ...(options.headers || {})
      },
      ...options,
    });

    const payload = await response.json().catch(() => ({
      success: false,
      message: 'Reponse serveur illisible.'
    }));

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || 'Action impossible.');
    }

    return payload;
  }

  return {
    request,
    searchUsers(query) {
      const params = new URLSearchParams({ q: query });
      return request(`/ajax/users/search?${params.toString()}`, { method: 'GET' });
    },
    setPrimaryCompany(userId, companyId) {
      return request(`/ajax/users/${userId}/set-primary-company`, {
        method: 'POST',
        body: JSON.stringify({ company_id: companyId })
      });
    }
  };
})();
