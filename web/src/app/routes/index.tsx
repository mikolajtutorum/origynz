import { createBrowserRouter } from 'react-router-dom';
import { Landing } from '../pages/Landing';
import { Login } from '../pages/Login';
import { Register } from '../pages/Register';
import { Dashboard } from '../pages/Dashboard';
import { Trees } from '../pages/Trees';
import { TreeWorkspace } from '../pages/TreeWorkspace';
import { MediaLibrary } from '../pages/MediaLibrary';
import { GedcomImport } from '../pages/GedcomImport';
import { RelationshipCalculator } from '../pages/RelationshipCalculator';
import { Duplicates } from '../pages/Duplicates';
import { Settings } from '../pages/Settings';
import { Admin } from '../pages/Admin';
import { NotFound } from '../pages/NotFound';
import { RedirectIfAuthenticated, RequireAuth, RequireSuperAdmin } from './guards';

export const router = createBrowserRouter([
  { path: '/', element: <Landing /> },
  {
    element: <RedirectIfAuthenticated />,
    children: [
      { path: '/login', element: <Login /> },
      { path: '/register', element: <Register /> },
    ],
  },
  {
    element: <RequireAuth />,
    children: [
      { path: '/dashboard', element: <Dashboard /> },
      { path: '/trees', element: <Trees /> },
      { path: '/trees/:id', element: <TreeWorkspace /> },
      { path: '/media', element: <MediaLibrary /> },
      { path: '/import', element: <GedcomImport /> },
      { path: '/relationship-calculator', element: <RelationshipCalculator /> },
      { path: '/duplicates', element: <Duplicates /> },
      { path: '/settings', element: <Settings /> },
    ],
  },
  {
    element: <RequireSuperAdmin />,
    children: [{ path: '/admin', element: <Admin /> }],
  },
  { path: '*', element: <NotFound /> },
]);
