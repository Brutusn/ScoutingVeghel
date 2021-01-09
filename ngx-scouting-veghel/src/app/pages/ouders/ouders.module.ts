import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ParentsPageComponent } from './parents-page/parents-page.component';
import { Routes, RouterModule } from '@angular/router';

const routes: Routes = [
  {
    path: '',
    component: ParentsPageComponent,
  },
];

@NgModule({
  declarations: [ParentsPageComponent],
  imports: [
    CommonModule,
    RouterModule.forChild(routes)
  ]
})
export class OudersModule { }
