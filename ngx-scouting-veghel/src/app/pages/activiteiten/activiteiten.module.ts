import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivitiesPageComponent } from './activities-page/activities-page.component';
import { RouterModule, Routes } from '@angular/router';
import { SvContentBlockModule } from '../../shared/sv-content-block/content-block.module';

const routes: Routes = [
  {
    path: '',
    component: ActivitiesPageComponent,
  },
];

@NgModule({
  declarations: [ActivitiesPageComponent],
  imports: [CommonModule, RouterModule.forChild(routes), SvContentBlockModule],
})
export class ActiviteitenModule {}
