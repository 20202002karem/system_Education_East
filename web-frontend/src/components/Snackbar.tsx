import { createContext, useCallback, useContext, useState, type ReactNode } from 'react';

interface SnackbarMessage { id: number; text: string; tone: 'success' | 'error' | 'info'; }
interface SnackbarContextValue { notify: (text: string, tone?: SnackbarMessage['tone']) => void; }

const SnackbarContext = createContext<SnackbarContextValue | undefined>(undefined);

const toneColors: Record<SnackbarMessage['tone'], string> = {
  success: 'var(--color-success)',
  error: 'var(--color-danger)',
  info: 'var(--color-text)',
};

export function SnackbarProvider({ children }: { children: ReactNode }) {
  const [messages, setMessages] = useState<SnackbarMessage[]>([]);

  const notify = useCallback((text: string, tone: SnackbarMessage['tone'] = 'info') => {
    const id = Date.now() + Math.random();
    setMessages((m) => [...m, { id, text, tone }]);
    setTimeout(() => setMessages((m) => m.filter((msg) => msg.id !== id)), 4000);
  }, []);

  return (
    <SnackbarContext.Provider value={{ notify }}>
      {children}
      <div style={{ position: 'fixed', bottom: 'var(--space-5)', insetInlineStart: 'var(--space-5)', display: 'flex', flexDirection: 'column', gap: 'var(--space-2)', zIndex: 2000 }}>
        {messages.map((m) => (
          <div key={m.id} role="status" style={{ background: '#1a1f27', color: '#fff', borderInlineStart: `4px solid ${toneColors[m.tone]}`, padding: 'var(--space-3) var(--space-4)', borderRadius: 'var(--radius-sm)', minWidth: 240, boxShadow: 'var(--shadow-md)' }}>
            {m.text}
          </div>
        ))}
      </div>
    </SnackbarContext.Provider>
  );
}

export function useSnackbar(): SnackbarContextValue {
  const ctx = useContext(SnackbarContext);
  if (!ctx) throw new Error('useSnackbar must be used within SnackbarProvider');
  return ctx;
}
