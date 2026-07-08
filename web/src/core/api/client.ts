import type { TokenStorage } from '../auth/storage';
import { getActiveLang, translate } from '../i18n/translate';

// Platform-agnostic API client. Uses the standard `fetch` API (available in the
// browser and in React Native), reads the bearer token from an injected
// TokenStorage, and is configured once at app startup via `configureApiClient`.

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
    public readonly payload?: unknown,
  ) {
    super(message);
    this.name = 'ApiError';
  }

  /** Laravel validation errors: { errors: { field: string[] } } */
  get validationErrors(): Record<string, string[]> | undefined {
    const p = this.payload as { errors?: Record<string, string[]> } | undefined;
    return p?.errors;
  }
}

export interface ApiClientConfig {
  baseUrl: string;
  tokenStorage: TokenStorage;
  /** Called whenever the API responds 401, so the app can drop the session. */
  onUnauthorized?: () => void;
}

let config: ApiClientConfig | null = null;

export function configureApiClient(c: ApiClientConfig): void {
  config = c;
}

function ensureConfig(): ApiClientConfig {
  if (!config) {
    throw new Error('API client not configured — call configureApiClient() at startup.');
  }
  return config;
}

/** Persist the bearer token in the configured storage (source of truth for requests). */
export async function persistToken(token: string): Promise<void> {
  await ensureConfig().tokenStorage.set(token);
}

/** Remove the persisted bearer token. */
export async function clearToken(): Promise<void> {
  await ensureConfig().tokenStorage.clear();
}

type QueryParams = Record<string, string | number | boolean | undefined | null>;

interface RequestOptions {
  method?: string;
  body?: unknown;
  query?: QueryParams;
  formData?: FormData;
  signal?: AbortSignal;
}

function safeJson(text: string): unknown {
  try {
    return JSON.parse(text);
  } catch {
    return null;
  }
}

/** Translate Laravel's { errors: { field: string[] } } validation messages in place. */
function translateErrorPayload(payload: unknown, lang: Parameters<typeof translate>[0]): unknown {
  if (!payload || typeof payload !== 'object') return payload;
  const { errors } = payload as { errors?: Record<string, string[]> };
  if (!errors) return payload;

  const translated: Record<string, string[]> = {};
  for (const [field, messages] of Object.entries(errors)) {
    translated[field] = messages.map((m) => translate(lang, m));
  }
  return { ...payload, errors: translated };
}

function buildUrl(baseUrl: string, path: string): URL {
  const trimmedBase = baseUrl.trim();
  if (!trimmedBase && typeof window !== 'undefined') {
    return new URL(path, window.location.origin);
  }

  if (trimmedBase.startsWith('/')) {
    const origin = typeof window !== 'undefined' ? window.location.origin : 'http://localhost';
    return new URL(`${trimmedBase}${path}`, origin);
  }

  return new URL(path, trimmedBase);
}

async function request<T>(path: string, opts: RequestOptions = {}): Promise<T> {
  const { baseUrl, tokenStorage, onUnauthorized } = ensureConfig();
  const token = await tokenStorage.get();

  const url = buildUrl(baseUrl, path);
  if (opts.query) {
    for (const [key, value] of Object.entries(opts.query)) {
      if (value !== undefined && value !== null) url.searchParams.set(key, String(value));
    }
  }

  const headers: Record<string, string> = { Accept: 'application/json' };
  if (token) headers.Authorization = `Bearer ${token}`;

  let body: BodyInit | undefined;
  if (opts.formData) {
    body = opts.formData; // browser/RN sets the multipart boundary automatically
  } else if (opts.body !== undefined) {
    headers['Content-Type'] = 'application/json';
    body = JSON.stringify(opts.body);
  }

  const res = await fetch(url.toString(), {
    method: opts.method ?? 'GET',
    headers,
    body,
    signal: opts.signal,
  });

  if (res.status === 401) onUnauthorized?.();

  const text = await res.text();
  const payload = text ? safeJson(text) : null;

  if (!res.ok) {
    // The API always emits fixed, canonical English text (see AppServiceProvider,
    // API controllers) — the SPA is the single source of translation for it.
    const lang = getActiveLang();
    const rawMessage =
      (payload as { message?: string } | null)?.message ?? res.statusText ?? 'Request failed';
    throw new ApiError(res.status, translate(lang, rawMessage), translateErrorPayload(payload, lang));
  }

  return payload as T;
}

/** Fetch a binary/file response with auth, for downloads. */
export async function getBlob(path: string): Promise<Blob> {
  const { baseUrl, tokenStorage } = ensureConfig();
  const token = await tokenStorage.get();
  const res = await fetch(buildUrl(baseUrl, path).toString(), {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  });
  if (!res.ok) throw new ApiError(res.status, res.statusText);
  return res.blob();
}

export const apiClient = {
  get: <T>(path: string, query?: QueryParams, signal?: AbortSignal) =>
    request<T>(path, { method: 'GET', query, signal }),
  post: <T>(path: string, body?: unknown) => request<T>(path, { method: 'POST', body }),
  put: <T>(path: string, body?: unknown) => request<T>(path, { method: 'PUT', body }),
  patch: <T>(path: string, body?: unknown) => request<T>(path, { method: 'PATCH', body }),
  delete: <T>(path: string, body?: unknown) => request<T>(path, { method: 'DELETE', body }),
  upload: <T>(path: string, formData: FormData) => request<T>(path, { method: 'POST', formData }),
};
