// Platform-agnostic token storage contract.
//
// Web injects a localStorage-backed implementation; a future React Native app
// injects an Expo SecureStore / AsyncStorage implementation. No other code in
// `core/` needs to change between platforms.
export interface TokenStorage {
  get(): string | null | Promise<string | null>;
  set(token: string): void | Promise<void>;
  clear(): void | Promise<void>;
}
