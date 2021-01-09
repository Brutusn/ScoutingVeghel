import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LeasePageComponent } from './lease-page/lease-page.component';
import { Routes, RouterModule } from '@angular/router';

const routes: Routes = [
  {
    path: '',
    component: LeasePageComponent,
  },
];

@NgModule({
  declarations: [LeasePageComponent],
  imports: [
    CommonModule,
    RouterModule.forChild(routes)
  ]
})
export class VerhuurModule { }
