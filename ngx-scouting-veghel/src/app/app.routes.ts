import { Routes } from '@angular/router';
import { NotFoundComponent } from './pages/home/not-found/not-found.component';
import { HomePageComponent } from './pages/home/home-page/home-page.component';

export const svRoutes: Routes = [
  {
    path: '',
    pathMatch: 'full',
    component: HomePageComponent,
  },
  {
    path: 'groepen',
    loadChildren: () =>
      import('./pages/groepen/groepen.module').then((m) => m.GroepenModule),
  },
  {
    path: 'activiteiten',
    loadChildren: () =>
      import('./pages/activiteiten/activiteiten.module').then(
        (m) => m.ActiviteitenModule
      ),
  },
  {
    path: 'ouders',
    loadChildren: () =>
      import('./pages/ouders/ouders.module').then((m) => m.OudersModule),
  },
  {
    path: 'staf',
    loadChildren: () =>
      import('./pages/staf/staf.module').then((m) => m.StafModule),
  },
  {
    path: 'contact',
    loadChildren: () =>
      import('./pages/contact/contact.module').then((m) => m.ContactModule),
  },
  {
    path: 'verhuur',
    loadChildren: () =>
      import('./pages/verhuur/verhuur.module').then((m) => m.VerhuurModule),
  },
  // This should be the last route!
  {
    path: '**',
    component: NotFoundComponent,
  },
];
