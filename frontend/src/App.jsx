import { useCallback, useMemo, useState } from 'react'
import heroImg from './assets/hero.png'
import { api, apiErrorMessage, bearer, compactParams } from './lib/api'
import './App.css'

const defaultEditor = {
  key: '',
  locale: 'en',
  locale_name: 'English',
  value: '',
  tags: 'web',
  description: '',
  is_published: true,
}

const defaultFilters = {
  locale: '',
  tag: '',
  key: '',
  content: '',
  q: '',
  per_page: 15,
}

function App() {
  const [token, setToken] = useState(() => localStorage.getItem('tms_token') || '')
  const [auth, setAuth] = useState({
    email: 'test@example.com',
    password: 'password',
    token_name: 'frontend-console',
  })
  const [filters, setFilters] = useState(defaultFilters)
  const [editor, setEditor] = useState(defaultEditor)
  const [editingId, setEditingId] = useState(null)
  const [translations, setTranslations] = useState([])
  const [tokens, setTokens] = useState([])
  const [exportLocale, setExportLocale] = useState('en')
  const [exportTag, setExportTag] = useState('')
  const [exportPayload, setExportPayload] = useState(null)
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(false)

  const headers = useMemo(() => (token ? bearer(token) : {}), [token])

  const loadTranslations = useCallback(
    async (nextFilters = defaultFilters) => {
      setLoading(true)
      setMessage('')

      try {
        const response = await api.get('/translations', {
          headers,
          params: compactParams(nextFilters),
        })

        setTranslations(response.data.data)
      } catch (error) {
        setMessage(apiErrorMessage(error))
      } finally {
        setLoading(false)
      }
    },
    [headers],
  )

  const loadTokens = useCallback(async () => {
    try {
      const response = await api.get('/auth/tokens', { headers })

      setTokens(response.data.data)
    } catch {
      setTokens([])
    }
  }, [headers])

  async function login(event) {
    event.preventDefault()
    setLoading(true)
    setMessage('')

    try {
      const response = await api.post('/auth/login', {
        ...auth,
        abilities: ['*'],
      })
      const issuedToken = response.data.data.plain_text_token
      const issuedHeaders = bearer(issuedToken)

      localStorage.setItem('tms_token', issuedToken)
      setToken(issuedToken)
      const [translationsResponse, tokensResponse] = await Promise.all([
        api.get('/translations', {
          headers: issuedHeaders,
          params: compactParams(defaultFilters),
        }),
        api.get('/auth/tokens', { headers: issuedHeaders }),
      ])
      setTranslations(translationsResponse.data.data)
      setTokens(tokensResponse.data.data)
      setMessage('Signed in')
    } catch (error) {
      setMessage(apiErrorMessage(error))
    } finally {
      setLoading(false)
    }
  }

  async function logout() {
    setLoading(true)

    try {
      if (token) {
        await api.post('/auth/logout', null, { headers })
      }
    } catch {
      // Local logout still clears a stale token.
    } finally {
      localStorage.removeItem('tms_token')
      setToken('')
      setTranslations([])
      setTokens([])
      setExportPayload(null)
      setLoading(false)
    }
  }

  async function refreshDashboard() {
    await Promise.all([loadTranslations(filters), loadTokens()])
  }

  async function saveTranslation(event) {
    event.preventDefault()
    setLoading(true)
    setMessage('')

    const payload = {
      ...editor,
      tags: editor.tags
        .split(',')
        .map((tag) => tag.trim())
        .filter(Boolean),
    }

    try {
      if (editingId) {
        await api.patch(`/translations/${editingId}`, payload, { headers })
        setMessage('Translation updated')
      } else {
        await api.post('/translations', payload, { headers })
        setMessage('Translation created')
      }

      setEditor(defaultEditor)
      setEditingId(null)
      await loadTranslations(filters)
    } catch (error) {
      setMessage(apiErrorMessage(error))
    } finally {
      setLoading(false)
    }
  }

  function editTranslation(translation) {
    setEditingId(translation.id)
    setEditor({
      key: translation.key,
      locale: translation.locale.code,
      locale_name: translation.locale.name,
      value: translation.value,
      tags: translation.tags.join(', '),
      description: translation.description || '',
      is_published: translation.is_published,
    })
  }

  async function deleteTranslation(id) {
    setLoading(true)
    setMessage('')

    try {
      await api.delete(`/translations/${id}`, { headers })
      setMessage('Translation deleted')
      await loadTranslations(filters)
    } catch (error) {
      setMessage(apiErrorMessage(error))
    } finally {
      setLoading(false)
    }
  }

  async function revokeToken(id) {
    setLoading(true)

    try {
      await api.delete(`/auth/tokens/${id}`, { headers })
      await loadTokens()
    } catch (error) {
      setMessage(apiErrorMessage(error))
    } finally {
      setLoading(false)
    }
  }

  async function exportTranslations(event) {
    event.preventDefault()
    setLoading(true)
    setMessage('')

    try {
      const response = await api.get(`/translations/export/${exportLocale}`, {
        headers,
        params: compactParams({ tag: exportTag }),
      })

      setExportPayload(response.data)
      setMessage('Export loaded')
    } catch (error) {
      setMessage(apiErrorMessage(error))
    } finally {
      setLoading(false)
    }
  }

  function downloadExport() {
    if (!exportPayload) {
      return
    }

    const blob = new Blob([JSON.stringify(exportPayload.translations, null, 2)], {
      type: 'application/json',
    })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')

    link.href = url
    link.download = `translations-${exportPayload.locale}.json`
    link.click()
    URL.revokeObjectURL(url)
  }

  function updateFilter(name, value) {
    const next = {
      ...filters,
      [name]: value,
    }

    setFilters(next)
  }

  return (
    <main className="app-shell">
      <aside className="sidebar">
        <div className="brand">
          <img src={heroImg} alt="" />
          <div>
            <strong>TMS</strong>
            <span>Translation Console</span>
          </div>
        </div>

        <form className="panel auth-panel" onSubmit={login}>
          <h1>API Access</h1>
          <label>
            Email
            <input
              value={auth.email}
              onChange={(event) =>
                setAuth({ ...auth, email: event.target.value })
              }
              type="email"
              autoComplete="email"
            />
          </label>
          <label>
            Password
            <input
              value={auth.password}
              onChange={(event) =>
                setAuth({ ...auth, password: event.target.value })
              }
              type="password"
              autoComplete="current-password"
            />
          </label>
          <label>
            Token name
            <input
              value={auth.token_name}
              onChange={(event) =>
                setAuth({ ...auth, token_name: event.target.value })
              }
            />
          </label>
          <button disabled={loading} type="submit">
            Sign in
          </button>
          <button disabled={!token || loading} onClick={logout} type="button">
            Sign out
          </button>
        </form>

        <section className="panel token-panel">
          <h2>Tokens</h2>
          <div className="token-list">
            {tokens.map((apiToken) => (
              <div className="token-row" key={apiToken.id}>
                <span>{apiToken.name}</span>
                <button
                  disabled={loading || apiToken.revoked_at}
                  onClick={() => revokeToken(apiToken.id)}
                  type="button"
                >
                  Revoke
                </button>
              </div>
            ))}
          </div>
        </section>
      </aside>

      <section className="workspace">
        <header className="topbar">
          <div>
            <p className="eyebrow">Locales, keys, tags, exports</p>
            <h2>Translation Management</h2>
          </div>
          <div className={token ? 'status online' : 'status'}>
            {token ? 'Connected' : 'Offline'}
          </div>
          <button disabled={!token || loading} onClick={refreshDashboard} type="button">
            Refresh
          </button>
        </header>

        {message ? <div className="notice">{message}</div> : null}

        <section className="panel search-panel">
          <form
            className="filter-grid"
            onSubmit={(event) => {
              event.preventDefault()
              loadTranslations(filters)
            }}
          >
            <label>
              Locale
              <input
                value={filters.locale}
                onChange={(event) => updateFilter('locale', event.target.value)}
                placeholder="en"
              />
            </label>
            <label>
              Tag
              <input
                value={filters.tag}
                onChange={(event) => updateFilter('tag', event.target.value)}
                placeholder="web"
              />
            </label>
            <label>
              Key
              <input
                value={filters.key}
                onChange={(event) => updateFilter('key', event.target.value)}
                placeholder="home.hero"
              />
            </label>
            <label>
              Content
              <input
                value={filters.content}
                onChange={(event) =>
                  updateFilter('content', event.target.value)
                }
                placeholder="Welcome"
              />
            </label>
            <button disabled={!token || loading} type="submit">
              Search
            </button>
          </form>
        </section>

        <div className="content-grid">
          <form className="panel editor-panel" onSubmit={saveTranslation}>
            <h2>{editingId ? 'Edit Translation' : 'Create Translation'}</h2>
            <label>
              Key
              <input
                value={editor.key}
                onChange={(event) =>
                  setEditor({ ...editor, key: event.target.value })
                }
                required
              />
            </label>
            <div className="two-col">
              <label>
                Locale
                <input
                  value={editor.locale}
                  onChange={(event) =>
                    setEditor({ ...editor, locale: event.target.value })
                  }
                  required
                />
              </label>
              <label>
                Locale name
                <input
                  value={editor.locale_name}
                  onChange={(event) =>
                    setEditor({ ...editor, locale_name: event.target.value })
                  }
                />
              </label>
            </div>
            <label>
              Value
              <textarea
                value={editor.value}
                onChange={(event) =>
                  setEditor({ ...editor, value: event.target.value })
                }
                required
                rows="5"
              />
            </label>
            <label>
              Tags
              <input
                value={editor.tags}
                onChange={(event) =>
                  setEditor({ ...editor, tags: event.target.value })
                }
              />
            </label>
            <label>
              Description
              <input
                value={editor.description}
                onChange={(event) =>
                  setEditor({ ...editor, description: event.target.value })
                }
              />
            </label>
            <label className="check-row">
              <input
                checked={editor.is_published}
                onChange={(event) =>
                  setEditor({ ...editor, is_published: event.target.checked })
                }
                type="checkbox"
              />
              Published
            </label>
            <div className="button-row">
              <button disabled={!token || loading} type="submit">
                {editingId ? 'Update' : 'Create'}
              </button>
              <button
                onClick={() => {
                  setEditingId(null)
                  setEditor(defaultEditor)
                }}
                type="button"
              >
                Clear
              </button>
            </div>
          </form>

          <section className="panel export-panel">
            <h2>JSON Export</h2>
            <form className="export-form" onSubmit={exportTranslations}>
              <label>
                Locale
                <input
                  value={exportLocale}
                  onChange={(event) => setExportLocale(event.target.value)}
                />
              </label>
              <label>
                Tag
                <input
                  value={exportTag}
                  onChange={(event) => setExportTag(event.target.value)}
                />
              </label>
              <button disabled={!token || loading} type="submit">
                Export
              </button>
              <button
                disabled={!exportPayload}
                onClick={downloadExport}
                type="button"
              >
                Download
              </button>
            </form>
            <pre className="export-preview">
              {exportPayload
                ? JSON.stringify(exportPayload.translations, null, 2)
                : '{}'}
            </pre>
          </section>
        </div>

        <section className="panel table-panel">
          <div className="table-header">
            <h2>Translations</h2>
            <span>{translations.length} loaded</span>
          </div>
          <div className="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Key</th>
                  <th>Locale</th>
                  <th>Value</th>
                  <th>Tags</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {translations.map((translation) => (
                  <tr key={translation.id}>
                    <td>{translation.key}</td>
                    <td>{translation.locale.code}</td>
                    <td>{translation.value}</td>
                    <td>{translation.tags.join(', ')}</td>
                    <td>{translation.is_published ? 'Published' : 'Draft'}</td>
                    <td>
                      <div className="row-actions">
                        <button
                          onClick={() => editTranslation(translation)}
                          type="button"
                        >
                          Edit
                        </button>
                        <button
                          onClick={() => deleteTranslation(translation.id)}
                          type="button"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      </section>
    </main>
  )
}

export default App
